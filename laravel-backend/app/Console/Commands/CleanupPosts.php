<?php

namespace App\Console\Commands;

use App\Models\LikedPost;
use App\Models\PostScreenshot;
use App\Models\CaptureSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupPosts extends Command
{
    protected $signature = 'posts:cleanup 
                           {--orphaned-screenshots : Remove screenshots without corresponding posts}
                           {--older-than=30days : Remove data older than specified period}
                           {--dry-run : Show what would be cleaned without making changes}';

    protected $description = 'Clean up orphaned files and old data';

    public function handle()
    {
        $orphanedScreenshots = $this->option('orphaned-screenshots');
        $olderThan = $this->option('older-than');
        $dryRun = $this->option('dry-run');

        $this->info('🧹 Starting cleanup operations...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $cleanupStats = [
            'orphaned_screenshots' => 0,
            'old_posts' => 0,
            'old_sessions' => 0,
            'freed_space_mb' => 0,
        ];

        if ($orphanedScreenshots) {
            $cleanupStats['orphaned_screenshots'] = $this->cleanOrphanedScreenshots($dryRun);
        }

        if ($olderThan) {
            $cutoffDate = $this->parsePeriod($olderThan);
            $cleanupStats['old_posts'] = $this->cleanOldPosts($cutoffDate, $dryRun);
            $cleanupStats['old_sessions'] = $this->cleanOldSessions($cutoffDate, $dryRun);
        }

        $this->displayCleanupReport($cleanupStats);

        return 0;
    }

    private function cleanOrphanedScreenshots(bool $dryRun): int
    {
        $this->line('🖼️  Checking for orphaned screenshots...');

        $orphanedScreenshots = PostScreenshot::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('liked_posts')
                  ->whereRaw('liked_posts.id = post_screenshots.liked_post_id');
        })->get();

        if ($orphanedScreenshots->isEmpty()) {
            $this->line('   ✓ No orphaned screenshots found');
            return 0;
        }

        $this->warn("   Found {$orphanedScreenshots->count()} orphaned screenshots");

        $removed = 0;
        foreach ($orphanedScreenshots as $screenshot) {
            if (!$dryRun) {
                if (Storage::exists($screenshot->image_path)) {
                    Storage::delete($screenshot->image_path);
                }
                $screenshot->delete();
            }
            $removed++;
            $this->line("   ✗ Removed: {$screenshot->image_path}");
        }

        return $removed;
    }

    private function cleanOldPosts(Carbon $cutoffDate, bool $dryRun): int
    {
        $this->line("🗓️  Cleaning posts older than {$cutoffDate->format('Y-m-d')}...");

        $oldPosts = LikedPost::where('liked_at', '<', $cutoffDate)->get();

        if ($oldPosts->isEmpty()) {
            $this->line('   ✓ No old posts found');
            return 0;
        }

        $this->warn("   Found {$oldPosts->count()} old posts");

        if (!$dryRun) {
            // Screenshots will be deleted via cascade
            LikedPost::where('liked_at', '<', $cutoffDate)->delete();
        }

        foreach ($oldPosts as $post) {
            $this->line("   ✗ Would remove: {$post->tweet_id} from {$post->liked_at->format('Y-m-d')}");
        }

        return $oldPosts->count();
    }

    private function cleanOldSessions(Carbon $cutoffDate, bool $dryRun): int
    {
        $this->line("📊 Cleaning capture sessions older than {$cutoffDate->format('Y-m-d')}...");

        $oldSessions = CaptureSession::where('capture_started_at', '<', $cutoffDate)->get();

        if ($oldSessions->isEmpty()) {
            $this->line('   ✓ No old sessions found');
            return 0;
        }

        $this->warn("   Found {$oldSessions->count()} old sessions");

        if (!$dryRun) {
            CaptureSession::where('capture_started_at', '<', $cutoffDate)->delete();
        }

        foreach ($oldSessions as $session) {
            $this->line("   ✗ Would remove session: {$session->browser_session_id} from {$session->capture_started_at->format('Y-m-d')}");
        }

        return $oldSessions->count();
    }

    private function parsePeriod(string $period): Carbon
    {
        $matches = [];
        if (preg_match('/(\d+)(days?|weeks?|months?|years?)/', $period, $matches)) {
            $amount = (int) $matches[1];
            $unit = rtrim($matches[2], 's'); // Remove plural 's'
            
            return now()->sub($unit, $amount);
        }

        throw new \InvalidArgumentException("Invalid period format: {$period}. Use format like '30days', '2weeks', '1month'");
    }

    private function displayCleanupReport(array $stats): void
    {
        $this->newLine();
        $this->info('📋 Cleanup Report:');
        $this->line("  Orphaned screenshots removed: {$stats['orphaned_screenshots']}");
        $this->line("  Old posts removed: {$stats['old_posts']}");
        $this->line("  Old sessions removed: {$stats['old_sessions']}");
        
        $totalItems = array_sum($stats);
        if ($totalItems > 0) {
            $this->info("  Total items cleaned: {$totalItems}");
        } else {
            $this->info('  Nothing needed cleaning ✨');
        }

        // Calculate freed space estimate
        $freedSpace = ($stats['orphaned_screenshots'] * 0.5) + ($stats['old_posts'] * 0.1); // Rough estimates
        $this->line("  Estimated space freed: " . number_format($freedSpace, 1) . " MB");
    }
}