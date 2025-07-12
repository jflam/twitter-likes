<?php

namespace App\Console\Commands;

use App\Models\LikedPost;
use App\Models\PostScreenshot;
use App\Models\ThreadRelationship;
use App\Models\CaptureSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostsStatus extends Command
{
    protected $signature = 'posts:status {--format=text : Output format (text|json)}';

    protected $description = 'Show status and statistics of captured posts';

    public function handle()
    {
        $format = $this->option('format');

        $stats = $this->collectStats();

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
        } else {
            $this->displayTextStatus($stats);
        }

        return 0;
    }

    private function collectStats(): array
    {
        $dbSize = $this->getDatabaseSize();
        $screenshotsSize = $this->getScreenshotsSize();
        $availableSpace = disk_free_space(storage_path());

        return [
            'database' => [
                'total_posts' => LikedPost::count(),
                'unprocessed_posts' => LikedPost::whereNull('updated_at')->count(),
                'screenshot_count' => PostScreenshot::count(),
                'thread_relationships' => ThreadRelationship::count(),
                'capture_sessions' => CaptureSession::count(),
            ],
            'storage' => [
                'database_size_mb' => round($dbSize / 1024 / 1024, 2),
                'screenshots_size_mb' => round($screenshotsSize / 1024 / 1024, 2),
                'available_space_mb' => round($availableSpace / 1024 / 1024, 2),
            ],
            'processing' => [
                'last_batch_processed' => LikedPost::latest('updated_at')->value('updated_at'),
                'processing_errors' => 0, // Would track in production
            ],
            'authors' => [
                'unique_authors' => LikedPost::distinct('author_username')->count(),
                'most_active_author' => $this->getMostActiveAuthor(),
            ],
            'content' => [
                'post_types' => $this->getPostTypeBreakdown(),
                'average_engagement' => $this->getAverageEngagement(),
            ]
        ];
    }

    private function displayTextStatus(array $stats): void
    {
        $this->info('🐦 Twitter Likes Extension - Status Report');
        $this->newLine();

        $this->line('📊 Database:');
        $this->line("  Total posts: {$stats['database']['total_posts']}");
        $this->line("  Unprocessed posts: {$stats['database']['unprocessed_posts']}");
        $this->line("  Screenshots: {$stats['database']['screenshot_count']}");
        $this->line("  Thread relationships: {$stats['database']['thread_relationships']}");
        $this->line("  Capture sessions: {$stats['database']['capture_sessions']}");
        $this->newLine();

        $this->line('💾 Storage:');
        $this->line("  Database size: {$stats['storage']['database_size_mb']} MB");
        $this->line("  Screenshots size: {$stats['storage']['screenshots_size_mb']} MB");
        $this->line("  Available space: {$stats['storage']['available_space_mb']} MB");
        $this->newLine();

        $this->line('👥 Authors:');
        $this->line("  Unique authors: {$stats['authors']['unique_authors']}");
        $this->line("  Most active: {$stats['authors']['most_active_author']}");
        $this->newLine();

        $this->line('📝 Content:');
        foreach ($stats['content']['post_types'] as $type => $count) {
            $this->line("  {$type}: {$count}");
        }
        $this->line("  Average engagement: {$stats['content']['average_engagement']}");
        $this->newLine();

        if ($stats['processing']['last_batch_processed']) {
            $this->line("🔄 Last processed: {$stats['processing']['last_batch_processed']}");
        } else {
            $this->warn("⚠️  No posts have been processed yet");
        }
    }

    private function getDatabaseSize(): int
    {
        $dbPath = database_path('database.sqlite');
        return file_exists($dbPath) ? filesize($dbPath) : 0;
    }

    private function getScreenshotsSize(): int
    {
        $totalSize = 0;
        $screenshots = PostScreenshot::all();
        
        foreach ($screenshots as $screenshot) {
            if (Storage::exists($screenshot->image_path)) {
                $totalSize += Storage::size($screenshot->image_path);
            }
        }
        
        return $totalSize;
    }

    private function getMostActiveAuthor(): string
    {
        $author = LikedPost::select('author_username')
            ->groupBy('author_username')
            ->orderByRaw('COUNT(*) DESC')
            ->first();

        return $author ? "@{$author->author_username}" : 'None';
    }

    private function getPostTypeBreakdown(): array
    {
        return LikedPost::select('post_type', DB::raw('count(*) as count'))
            ->groupBy('post_type')
            ->pluck('count', 'post_type')
            ->toArray();
    }

    private function getAverageEngagement(): string
    {
        $avg = LikedPost::selectRaw('AVG(like_count + retweet_count + reply_count) as avg_engagement')
            ->value('avg_engagement');

        return number_format($avg ?? 0, 1);
    }
}