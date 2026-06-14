<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\RedirectorLinkCleanerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CleanRedirectorLinks extends Command
{
    protected $signature = 'posts:clean-redirector-links
        {--commit : Save cleaned links to the database. Without this option, only previews changes}
        {--limit=0 : Maximum matching posts to inspect; 0 means no limit}
        {--chunk=200 : Number of posts to process per batch}
        {--from-id= : Only process posts with postid greater than or equal to this value}
        {--to-id= : Only process posts with postid less than or equal to this value}
        {--report= : CSV report path for affected topic URLs; defaults to storage/app/redirector-link-cleaner-report.csv}
        {--sample=10 : Number of changed posts to show as examples}';

    protected $description = 'Clean legacy redirector.php?url= links in forum post content.';

    public function handle(RedirectorLinkCleanerService $cleaner): int
    {
        $commit = (bool) $this->option('commit');
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = min(1000, max(1, (int) $this->option('chunk')));
        $sampleLimit = max(0, (int) $this->option('sample'));
        $fromId = $this->option('from-id') !== null ? (int) $this->option('from-id') : null;
        $toId = $this->option('to-id') !== null ? (int) $this->option('to-id') : null;
        $reportPath = (string) ($this->option('report') ?: storage_path('app/redirector-link-cleaner-report.csv'));

        $query = Post::query()
            ->with(['thread:threadid,title'])
            ->select(['postid', 'threadid', 'pagetext'])
            ->whereNotNull('pagetext')
            ->where(function ($q) {
                $q->where('pagetext', 'like', '%redirector.php%')
                    ->orWhere('pagetext', 'like', '%http://%http://%')
                    ->orWhere('pagetext', 'like', '%http://%https://%')
                    ->orWhere('pagetext', 'like', '%https://%http://%')
                    ->orWhere('pagetext', 'like', '%https://%https://%');
            })
            ->when($fromId !== null, fn ($q) => $q->where('postid', '>=', $fromId))
            ->when($toId !== null, fn ($q) => $q->where('postid', '<=', $toId))
            ->orderBy('postid');

        $matchingPosts = (clone $query)->count();
        $targetPosts = $limit > 0 ? min($matchingPosts, $limit) : $matchingPosts;

        $this->info('Redirector link cleaner');
        $this->line($commit ? 'Mode: COMMIT (database will be updated)' : 'Mode: PREVIEW (no database changes)');
        $this->line('Scope: all matching posts' . ($limit > 0 ? " (limited to {$limit})" : ''));

        if ($targetPosts === 0) {
            $this->info('No posts containing redirector or malformed URL candidates were found.');
            return self::SUCCESS;
        }

        $processed = 0;
        $changedPosts = 0;
        $changedLinks = 0;
        $examples = [];
        $changedTopics = [];
        $remaining = $limit > 0 ? $limit : null;

        $bar = $this->output->createProgressBar($targetPosts);
        $bar->start();

        $query->chunkById($chunkSize, function ($posts) use (
            $cleaner,
            $commit,
            $sampleLimit,
            &$processed,
            &$changedPosts,
            &$changedLinks,
            &$examples,
            &$changedTopics,
            &$remaining,
            $bar
        ) {
            foreach ($posts as $post) {
                if ($remaining !== null && $remaining <= 0) {
                    return false;
                }

                $processed++;
                if ($remaining !== null) {
                    $remaining--;
                }

                $result = $cleaner->cleanContent((string) $post->pagetext);

                if ($result['replacements'] > 0 && $result['content'] !== $post->pagetext) {
                    $changedPosts++;
                    $changedLinks += $result['replacements'];

                    if (count($examples) < $sampleLimit) {
                        $firstLink = $result['links'][0];
                        $examples[] = [
                            'postid' => $post->postid,
                            'thread_url' => Str::limit($this->threadUrl($post), 80),
                            'links' => $result['replacements'],
                            'from' => Str::limit($firstLink['from'], 80),
                            'to' => Str::limit($firstLink['to'], 80),
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

        if ($examples !== []) {
            $this->table(['postid', 'thread_url', 'links', 'from', 'to'], $examples);
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Matching posts inspected', $processed],
                ['Posts changed', $changedPosts],
                ['Redirector/malformed links cleaned', $changedLinks],
                ['Topics affected', count($changedTopics)],
            ]
        );

        if ($changedTopics !== []) {
            $this->writeTopicReport($reportPath, $changedTopics);
            $this->info("Affected topic report: {$reportPath}");
            $this->table(
                ['threadid', 'changed_posts', 'changed_links', 'thread_url'],
                array_map(
                    fn (array $topic) => [
                        'threadid' => $topic['threadid'],
                        'changed_posts' => $topic['changed_posts'],
                        'changed_links' => $topic['changed_links'],
                        'thread_url' => Str::limit($topic['thread_url'], 100),
                    ],
                    array_slice(array_values($changedTopics), 0, $sampleLimit)
                )
            );

            if (count($changedTopics) > $sampleLimit) {
                $this->line('Console topic list is sampled; the CSV report contains all affected topics.');
            }
        }

        if (!$commit) {
            $this->warn('Preview complete. Run again with --commit to update the database.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{threadid:int|string,title:string,thread_url:string,changed_posts:int,changed_links:int,postids:array<int, int>}>  $changedTopics
     */
    private function addTopicReportRow(array &$changedTopics, Post $post, int $changedLinks): void
    {
        $threadId = $post->threadid ?: 'post-' . $post->postid;
        $threadUrl = $this->threadUrl($post);

        if (!isset($changedTopics[$threadId])) {
            $changedTopics[$threadId] = [
                'threadid' => $threadId,
                'title' => (string) ($post->thread?->title ?? ''),
                'thread_url' => $threadUrl,
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
