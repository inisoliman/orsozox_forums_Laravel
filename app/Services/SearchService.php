<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * Check if FULLTEXT indexes exist on the required tables.
     * Cached for 1 hour to avoid repeated SHOW INDEX queries.
     */
    protected function hasFulltextIndexes(): bool
    {
        return Cache::remember('search_has_fulltext', 3600, function () {
            try {
                $threadIdx = DB::select("SHOW INDEX FROM thread WHERE Key_name = 'ft_thread_title'");
                $postIdx = DB::select("SHOW INDEX FROM post WHERE Key_name = 'ft_post_pagetext'");
                return !empty($threadIdx) && !empty($postIdx);
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    /**
     * Instant AJAX suggest — searches thread.title only.
     * Uses FULLTEXT if available, falls back to LIKE.
     *
     * @param string $query  Search query (minimum 3 chars)
     * @return array         Array of suggestion objects
     */
    public function suggest(string $query): array
    {
        $cacheKey = 'search_suggest_' . md5(mb_strtolower($query, 'UTF-8'));

        return Cache::remember($cacheKey, 300, function () use ($query) {
            try {
                if ($this->hasFulltextIndexes()) {
                    return $this->suggestFulltext($query);
                }
                return $this->suggestLike($query);
            } catch (\Exception $e) {
                Log::warning('Search suggest FULLTEXT failed, falling back to LIKE: ' . $e->getMessage());
                // Clear cached index status so it re-checks
                Cache::forget('search_has_fulltext');
                return $this->suggestLike($query);
            }
        });
    }

    /**
     * FULLTEXT-based suggestion search.
     */
    protected function suggestFulltext(string $query): array
    {
        $booleanQuery = $this->prepareBooleanQuery($query);

        $results = DB::select("
            SELECT 
                t.threadid,
                t.title,
                f.title AS forum_name,
                u.username,
                MATCH(t.title) AGAINST(? IN BOOLEAN MODE) AS relevance_score
            FROM thread t
            LEFT JOIN forum f ON f.forumid = t.forumid
            LEFT JOIN user u ON u.userid = t.postuserid
            WHERE t.visible = 1
              AND MATCH(t.title) AGAINST(? IN BOOLEAN MODE)
            ORDER BY relevance_score DESC
            LIMIT 7
        ", [$booleanQuery, $booleanQuery]);

        return $this->formatSuggestions($results);
    }

    /**
     * LIKE-based suggestion search (fallback).
     */
    protected function suggestLike(string $query): array
    {
        $results = DB::select("
            SELECT 
                t.threadid,
                t.title,
                f.title AS forum_name,
                u.username
            FROM thread t
            LEFT JOIN forum f ON f.forumid = t.forumid
            LEFT JOIN user u ON u.userid = t.postuserid
            WHERE t.visible = 1
              AND t.title LIKE ?
            ORDER BY t.dateline DESC
            LIMIT 7
        ", ['%' . $query . '%']);

        return $this->formatSuggestions($results);
    }

    /**
     * Format raw DB results into suggestion array.
     */
    protected function formatSuggestions(array $results): array
    {
        return array_map(function ($row) {
            return [
                'threadid' => (int) $row->threadid,
                'title' => strip_tags($row->title),
                'forum_name' => strip_tags($row->forum_name ?? ''),
                'username' => $row->username ?? 'زائر',
                'url' => route('thread.show', [
                    'id' => $row->threadid,
                    'slug' => $this->createSlug($row->title),
                ]),
            ];
        }, $results);
    }

    /**
     * Advanced search — searches thread.title using FULLTEXT.
     * 
     * PERFORMANCE OPTIMIZATION (v1.1.1):
     * - Searches ONLY thread.title (not post.pagetext) to avoid timeout
     * - Thread title search is fast (~70K rows with FULLTEXT index)
     * - Avoids correlated subqueries on 600K+ post table
     * - Falls back to LIKE if FULLTEXT indexes don't exist
     *
     * @param string $query   Search query
     * @param array  $filters ['forumid' => ?int, 'userid' => ?int]
     * @param string $sort    'relevance'|'newest'|'most_viewed'|'most_replies'
     * @param int    $perPage Results per page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function advancedSearch(string $query, array $filters = [], string $sort = 'relevance', int $perPage = 20)
    {
        $hasFulltext = $this->hasFulltextIndexes();

        // Build the base query
        $builder = DB::table('thread as t')
            ->select([
                't.threadid',
                't.title',
                't.forumid',
                't.postuserid',
                't.postusername',
                't.dateline',
                't.lastpost',
                't.views',
                't.replycount',
                't.firstpostid',
                'f.title as forum_name',
                'u.username',
            ])
            ->leftJoin('forum as f', 'f.forumid', '=', 't.forumid')
            ->leftJoin('user as u', 'u.userid', '=', 't.postuserid')
            ->where('t.visible', 1);

        // Search condition — title only for speed
        if ($hasFulltext) {
            $booleanQuery = $this->prepareBooleanQuery($query);

            try {
                $builder->whereRaw('MATCH(t.title) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);

                // Add relevance score for sorting
                if ($sort === 'relevance') {
                    $builder->addSelect(DB::raw(
                        'MATCH(t.title) AGAINST(' . DB::getPdo()->quote($booleanQuery) . ' IN BOOLEAN MODE) AS total_score'
                    ));
                }
            } catch (\Exception $e) {
                Log::warning('FULLTEXT search failed, using LIKE: ' . $e->getMessage());
                Cache::forget('search_has_fulltext');
                $builder->where('t.title', 'LIKE', '%' . $query . '%');
                $sort = 'newest'; // Can't sort by relevance without FULLTEXT
            }
        } else {
            // Fallback to LIKE
            $builder->where('t.title', 'LIKE', '%' . $query . '%');
            if ($sort === 'relevance') {
                $sort = 'newest'; // Can't sort by relevance with LIKE
            }
        }

        // Apply filters
        if (!empty($filters['forumid'])) {
            $builder->where('t.forumid', (int) $filters['forumid']);
        }

        if (!empty($filters['userid'])) {
            $builder->where('t.postuserid', (int) $filters['userid']);
        }

        // Apply sorting
        switch ($sort) {
            case 'newest':
                $builder->orderByDesc('t.dateline');
                break;
            case 'most_viewed':
                $builder->orderByDesc('t.views');
                break;
            case 'most_replies':
                $builder->orderByDesc('t.replycount');
                break;
            case 'relevance':
            default:
                $builder->orderByDesc('total_score');
                break;
        }

        // Use simplePaginate to avoid expensive COUNT(*)
        return $builder->simplePaginate($perPage)->appends(request()->query());
    }

    /**
     * Get an excerpt from the first post of a thread.
     * Uses a simple, fast query — no FULLTEXT needed.
     *
     * @param int $threadid
     * @return string|null
     */
    public function getFirstPostExcerpt(int $threadid): ?string
    {
        $post = DB::selectOne("
            SELECT pagetext
            FROM post
            WHERE threadid = ? AND visible = 1
            ORDER BY dateline ASC
            LIMIT 1
        ", [$threadid]);

        return $post->pagetext ?? null;
    }

    /**
     * Prepare a Boolean Mode query string from user input.
     *
     * @param string $query  Raw user input
     * @return string        Boolean Mode query
     */
    protected function prepareBooleanQuery(string $query): string
    {
        // Remove special MySQL FULLTEXT characters to prevent injection
        $query = preg_replace('/[+\-><()~*"@]/u', ' ', $query);
        $query = preg_replace('/\s+/u', ' ', trim($query));

        if (empty($query)) {
            return '';
        }

        $words = explode(' ', $query);
        $booleanParts = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word, 'UTF-8') >= 2) {
                $booleanParts[] = '+' . $word . '*';
            }
        }

        return implode(' ', $booleanParts);
    }

    /**
     * Create URL slug (same logic as Thread model).
     */
    protected function createSlug(?string $text): string
    {
        if (empty($text))
            return 'thread';
        $text = strip_tags($text);
        $text = trim($text);
        $text = preg_replace('/\s+/u', '-', $text);
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'thread';
    }
}
