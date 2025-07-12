<?php

namespace App\Console\Commands;

use App\Models\LikedPost;
use App\Models\ThreadRelationship;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessPosts extends Command
{
    protected $signature = 'posts:process 
                           {--batch-size=50 : Number of posts to process in each batch}
                           {--dry-run : Show what would be processed without making changes}';

    protected $description = 'Process captured posts, extract metadata, build relationships';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        $this->info('Processing captured posts...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $unprocessedPosts = LikedPost::whereNull('updated_at')
            ->orWhere('updated_at', '<', 'created_at')
            ->limit($batchSize)
            ->get();

        if ($unprocessedPosts->isEmpty()) {
            $this->info('No unprocessed posts found.');
            return 0;
        }

        $this->info("Found {$unprocessedPosts->count()} unprocessed posts");

        $processed = 0;
        $errors = 0;

        foreach ($unprocessedPosts as $post) {
            try {
                if (!$dryRun) {
                    $this->processPost($post);
                }
                $processed++;
                $this->line("✓ Processed: {$post->tweet_id} by @{$post->author_username}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to process: {$post->tweet_id} - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Processing complete:");
        $this->line("  Processed: $processed");
        $this->line("  Errors: $errors");

        return $errors > 0 ? 1 : 0;
    }

    private function processPost(LikedPost $post)
    {
        DB::beginTransaction();

        try {
            // Extract language if not set
            if (empty($post->language_code)) {
                $post->language_code = $this->detectLanguage($post->content_text);
            }

            // Check if this is part of a thread
            if ($this->isThreadPost($post)) {
                $post->is_thread_post = true;
                $post->thread_position = $this->calculateThreadPosition($post);
            }

            // Update processing timestamp
            $post->touch();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function detectLanguage(string $text): string
    {
        // Simple language detection - in production would use a proper library
        if (preg_match('/[а-яё]/ui', $text)) {
            return 'ru';
        }
        if (preg_match('/[中文]/u', $text)) {
            return 'zh';
        }
        if (preg_match('/[ひらがなカタカナ]/u', $text)) {
            return 'ja';
        }
        return 'en';
    }

    private function isThreadPost(LikedPost $post): bool
    {
        // Check if post is part of existing thread relationships
        return ThreadRelationship::where('child_post_id', $post->id)
            ->orWhere('parent_post_id', $post->id)
            ->orWhere('root_post_id', $post->id)
            ->exists();
    }

    private function calculateThreadPosition(LikedPost $post): int
    {
        $relationship = ThreadRelationship::where('child_post_id', $post->id)->first();
        return $relationship ? $relationship->depth_level + 1 : 1;
    }
}