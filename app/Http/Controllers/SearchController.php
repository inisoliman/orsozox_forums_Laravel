<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use App\Helpers\SearchHighlightHelper;
use App\Models\Forum;
use Illuminate\Support\Facades\RateLimiter;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * AJAX Instant Search — triggered after 3 characters with 300ms debounce.
     *
     * GET /search/suggest?q=...
     * Returns JSON array of thread suggestions.
     *
     * Rate limit: 30 requests/minute per IP
     */
    public function suggest(Request $request)
    {
        // Rate limiting — 30 requests per minute per IP
        $key = 'search_suggest_' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'error' => 'تم تجاوز حد البحث. حاول بعد قليل.',
                'results' => [],
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // Validation
        $request->validate([
            'q' => 'required|string|min:3|max:100',
        ]);

        $query = trim($request->input('q'));
        $results = $this->searchService->suggest($query);

        return response()->json([
            'results' => $results,
            'query' => $query,
        ]);
    }

    /**
     * Advanced Search Page — FULLTEXT search in titles.
     *
     * GET /search?q=...&forumid=...&userid=...&sort=...
     *
     * Rate limit: 10 requests/minute per IP
     */
    public function index(Request $request)
    {
        $query = trim($request->input('q', ''));
        $results = null;
        $excerpts = [];

        // Get forums list for filter dropdown
        $forums = Forum::active()->ordered()->get(['forumid', 'title']);

        if (!empty($query)) {
            // Validate
            $request->validate([
                'q' => 'required|string|min:3|max:100',
                'forumid' => 'nullable|integer|min:1',
                'userid' => 'nullable|integer|min:1',
                'sort' => 'nullable|in:relevance,newest,most_viewed,most_replies',
            ]);

            // Rate limiting — 10 full searches per minute per IP
            $key = 'search_full_' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                return view('search', [
                    'results' => null,
                    'query' => $query,
                    'forums' => $forums,
                    'excerpts' => [],
                    'error' => 'تم تجاوز حد البحث. حاول مرة أخرى بعد دقيقة.',
                ]);
            }
            RateLimiter::hit($key, 60);

            $filters = [
                'forumid' => $request->input('forumid'),
                'userid' => $request->input('userid'),
            ];
            $sort = $request->input('sort', 'relevance');

            try {
                $results = $this->searchService->advancedSearch($query, $filters, $sort);

                // Get excerpts — use first post of each thread (fast, no FULLTEXT needed)
                foreach ($results as $thread) {
                    $pagetext = $this->searchService->getFirstPostExcerpt($thread->threadid);
                    $excerpts[$thread->threadid] = SearchHighlightHelper::highlight(
                        $pagetext ?? '',
                        $query,
                        280
                    );
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Search error: ' . $e->getMessage());
                return view('search', [
                    'results' => null,
                    'query' => $query,
                    'forums' => $forums,
                    'excerpts' => [],
                    'error' => 'حدث خطأ أثناء البحث. يرجى المحاولة مرة أخرى.',
                ]);
            }
        }

        return view('search', [
            'results' => $results,
            'query' => $query,
            'forums' => $forums,
            'excerpts' => $excerpts,
        ]);
    }
}
