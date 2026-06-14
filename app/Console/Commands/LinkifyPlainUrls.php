<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PlainUrlLinkifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LinkifyPlainUrls extends Command
{
    protected $signature = 'posts:linkify-plain-urls
        {--commit : Save linkified URLs to the database. Without this option, only previews changes}
        {--limit=0 : Maximum matching posts to inspect; 0 means no limit}
        {--chunk=200 : Number of posts to process per batch}
        {--from-id= : Only process posts with postid greater than or equal to this value}
        {--to-id= : Only process posts with postid less than or equal to this value}
        {--report= : CSV report path; defaults to storage/app/plain-url-linkifier-report.csv}
        {--sample=10 : Number of changed posts/topics to show as examples}';

    protected $description = 'Convert plain text URLs in forum posts into clickable links.';

    public function handle(PlainUrlLinkifierService $linkifier): int
    {
        $commit = (bool) $this->option('commit');
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = min(1000, max(1, (int) $this->option('chunk')));
        $sampleLimit = max(0, (int) $this->option('sample'));
        $fromId = $this->option('from-id') !== null ? (int) $this->option('from-id') : null;
        $toId = $this->option('to-id') !== null ? (int) $this->option('to-id') : null;
        $reportPath = (string) ($this->option('report') ?: storage_path('app/plain-url-linkifier-report.csv'));

        $query = Post::query()
            ->with(['thread:threadid,title'])
            ->select(['postid', 'threadid', 'pagetext'])
            ->whereNotNull('pagetext')
            ->where(function ($q) {
                $q->where('pagetext', 'like', '%http://%')
                    ->orWhere('pagetext', 'like', '%https://%')
                    ->orWhere('pagetext', 'like', '%www.%');
            })
            ->when($fromId !== null, fn ($q) => $q->where('postid', '>=', $fromId))
            ->when($toId !== null, fn ($q) => $q->where('postid', '<=', $toId))
            ->orderBy('postid');

        $matchingPosts = (clone $query)->count();
        $targetPosts = $limit > 0 ? min($matchingPosts, $limit) : $matchingPosts;

        $this->info('Plain URL linkifier');
        $this->line($commit ? 'Mode: COMMIT (database will be updated)' : 'Mode: PREVIEW (no database changes)');
        $this->line('Scope: all posts containing URLs' . ($limit > 0 ? " (limited to {$limit})" : ''));

        if ($targetPosts === 0) {
            $this->info('No posts containing plain URL candidates were found.');
            return self::SUCCESS;
        }

        $stats = [
            'processed' => 0,
            'changed_posts' => 0,
            'changed_links' => 0,
        ];
        $examples = [];
        $changedTopics = [];
        $remaining = $limit > 0 ? $limit : null;

        $bar = $this->output->createProgressBar($targetPosts);
        $bar->start();

        $query->chunkById($chunkSize, function ($posts) use (
            $linkifier,
            $commit,
            $sampleLimit,
            &$stats,
            &$examples,
            &$changedTopics,
            &$remaining,
            $bar
        ) {
            foreach ($posts as $post) {
                if ($remaining !== null && $remaining <= 0) {
                    return false;
                }

                $stats['processed']++;
                if ($remaining !== null) {
                    $remaining--;
                }

                $result = $linkifier->linkifyContent((string) $post->pagetext);

                if ($result['replacements'] > 0 && $result['content'] !== $post->pagetext) {
                    $stats['changed_posts']++;
                    $stats['changed_links'] += $result['replacements'];

                    if (count($examples) < $sampleLimit) {
                        $firstLink = $result['links'][0];
                        $examples[] = [
                            'postid' => $post->postid,
                            'thread_url' => Str::limit($this->threadUrl($post), 80),
                            'links' => $result['replacements'],
                            'url' => Str::limit($firstLink['from'], 80),
                        ];
                    }

                    $this->addTopicReportRow($changedTopics, $post, $result['replacements']);

                    if ($commit) {
                        Post::whereKey($post->postid)->update([
                            'pagetext' => $result['content'],
                        ]);
                    }
                }

                $bar->advance();
            }

            return $remaining === null || $remaining > 0;
        }, 'postid', 'postid');

        $bar->finish();
        $this->newLine(2);

        $this->printExamples($examples, $sampleLimit);
        $this->printSummary($stats, $changedTopics);

        if ($changedTopics !== []) {
            $this->writeTopicReport($reportPath, $changedTopics);
            $this->info("Affected topic report: {$reportPath}");
        }

        if (!$commit) {
            $this->warn('Preview complete. Run again with --commit to update the database.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $examples
     */
    private function printExamples(array $examples, int $sampleLimit): void
    {
        if ($examples === []) {
            return;
        }

        $this->table(['postid', 'thread_url', 'links', 'url'], $examples);

        if (count($examples) >= $sampleLimit) {
            $this->line('Console examples are sampled; the CSV report contains all affected topics.');
        }
    }

    /**
     * @param  array{processed:int,changed_posts:int,changed_links:int}  $stats
     * @param  array<string, array<string, mixed>>  $changedTopics
     */
    private function printSummary(array $stats, array $changedTopics): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Matching posts inspected', $stats['processed']],
                ['Posts changed', $stats['changed_posts']],
                ['Plain URLs linkified', $stats['changed_links']],
                ['Topics affected', count($changedTopics)],
            ]
        );
    }

    /**
     * @param  array<string, array{threadid:int|string,title:string,thread_url:string,changed_posts:int,changed_links:int,postids:array<int, int>}>  $changedTopics
     */
    private function addTopicReportRow(array &$changedTopics, Post $post, int $changedLinks): void
    {
        $threadId = $post->threadid ?: 'post-' . $post->postid;

        if (!isset($changedTopics[$threadId])) {
            $changedTopics[$threadId] = [
                'threadid' => $threadId,
                'title' => (string) ($post->thread?->title ?? ''),
                'thread_url' => $this->threadUrl($post),
                'changed_posts' => 0,
                'changed_links' => 0,
                'postids' => [],
            ];
        }

        $changedTopics[$threadId]['changed_posts']++;
        $changedTopics[$threadId]['changed_links'] += $changedLinks;
        $changedTopics[$threadId]['postids'][] = (int) $post->postid;
    }

    private function threadUrl(Post $post): string
    {
        if ($post->thread) {
            return $post->thread->url;
        }

        return "missing-thread:{$post->threadid}#post-{$post->postid}";
    }

    /**
     * @param  array<string, array{threadid:int|string,title:string,thread_url:string,changed_posts:int,changed_links:int,postids:array<int, int>}>  $changedTopics
     */
    private function writeTopicReport(string $reportPath, array $changedTopics): void
    {
        File::ensureDirectoryExists(dirname($reportPath));

        $handle = fopen($reportPath, 'w');
        if ($handle === false) {
            $this->warn("Could not write topic report to {$reportPath}");
            return;
        }

        fputcsv($handle, ['threadid', 'title', 'thread_url', 'changed_posts', 'changed_links', 'postids']);

        foreach ($changedTopics as $topic) {
            fputcsv($handle, [
                $topic['threadid'],
                $topic['title'],
                $topic['thread_url'],
                $topic['changed_posts'],
                $topic['changed_links'],
                implode('|', $topic['postids']),
            ]);
        }

        fclose($handle);
    }
}
