<?php

namespace App\Console\Commands;

use App\Models\LikedPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportPosts extends Command
{
    protected $signature = 'posts:export 
                           {--format=json : Export format (json|csv|sql)}
                           {--output= : Output file path (optional)}';

    protected $description = 'Export captured posts data for external analysis';

    public function handle()
    {
        $format = $this->option('format');
        $outputPath = $this->option('output');

        if (!in_array($format, ['json', 'csv', 'sql'])) {
            $this->error('Invalid format. Use: json, csv, or sql');
            return 1;
        }

        $this->info("Exporting posts in {$format} format...");

        try {
            $posts = LikedPost::with(['screenshot', 'parentRelationship', 'childRelationships'])
                ->orderBy('liked_at')
                ->get();

            if ($posts->isEmpty()) {
                $this->warn('No posts found to export');
                return 0;
            }

            $data = $this->exportData($posts, $format);
            
            if ($outputPath) {
                $this->saveToFile($data, $outputPath);
                $this->info("Exported {$posts->count()} posts to: {$outputPath}");
            } else {
                $this->line($data);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function exportData($posts, string $format): string
    {
        switch ($format) {
            case 'json':
                return $this->exportJson($posts);
            case 'csv':
                return $this->exportCsv($posts);
            case 'sql':
                return $this->exportSql($posts);
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    private function exportJson($posts): string
    {
        $data = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'tweet_id' => $post->tweet_id,
                'author' => [
                    'username' => $post->author_username,
                    'display_name' => $post->author_display_name,
                    'avatar_url' => $post->author_avatar_url,
                ],
                'content' => [
                    'text' => $post->content_text,
                    'html' => $post->content_html,
                    'language' => $post->language_code,
                ],
                'metadata' => [
                    'post_url' => $post->post_url,
                    'posted_at' => $post->posted_at->toISOString(),
                    'liked_at' => $post->liked_at->toISOString(),
                    'captured_at' => $post->captured_at->toISOString(),
                    'post_type' => $post->post_type,
                ],
                'engagement' => [
                    'likes' => $post->like_count,
                    'retweets' => $post->retweet_count,
                    'replies' => $post->reply_count,
                    'views' => $post->view_count,
                ],
                'thread' => [
                    'is_thread_post' => $post->is_thread_post,
                    'position' => $post->thread_position,
                    'has_parent' => $post->parentRelationship !== null,
                    'has_children' => $post->childRelationships->isNotEmpty(),
                ],
                'screenshot' => $post->screenshot ? [
                    'path' => $post->screenshot->image_path,
                    'format' => $post->screenshot->image_format,
                    'size_bytes' => $post->screenshot->image_size_bytes,
                    'dimensions' => [
                        'width' => $post->screenshot->screenshot_width,
                        'height' => $post->screenshot->screenshot_height,
                    ],
                    'quality_score' => $post->screenshot->quality_score,
                ] : null,
            ];
        });

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function exportCsv($posts): string
    {
        $headers = [
            'tweet_id', 'author_username', 'author_display_name', 'content_text',
            'post_url', 'posted_at', 'liked_at', 'post_type', 'like_count',
            'retweet_count', 'reply_count', 'view_count', 'is_thread_post',
            'thread_position', 'has_screenshot', 'language_code'
        ];

        $csv = implode(',', $headers) . "\n";

        foreach ($posts as $post) {
            $row = [
                $this->escapeCsv($post->tweet_id),
                $this->escapeCsv($post->author_username),
                $this->escapeCsv($post->author_display_name),
                $this->escapeCsv($post->content_text),
                $this->escapeCsv($post->post_url),
                $post->posted_at->toISOString(),
                $post->liked_at->toISOString(),
                $post->post_type,
                $post->like_count,
                $post->retweet_count,
                $post->reply_count,
                $post->view_count ?? 0,
                $post->is_thread_post ? 'true' : 'false',
                $post->thread_position ?? 0,
                $post->screenshot ? 'true' : 'false',
                $post->language_code ?? 'unknown',
            ];

            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    private function exportSql($posts): string
    {
        $sql = "-- Twitter Likes Export - Generated on " . now()->toISOString() . "\n\n";
        $sql .= "INSERT INTO liked_posts (id, tweet_id, author_username, author_display_name, content_text, post_url, posted_at, liked_at, post_type, like_count, retweet_count, reply_count, view_count, is_thread_post, thread_position, language_code, created_at, updated_at) VALUES\n";

        $values = [];
        foreach ($posts as $post) {
            $values[] = sprintf(
                "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %s, %s, %s, '%s', '%s', '%s')",
                $post->id,
                addslashes($post->tweet_id),
                addslashes($post->author_username),
                addslashes($post->author_display_name),
                addslashes($post->content_text),
                addslashes($post->post_url),
                $post->posted_at->toDateTimeString(),
                $post->liked_at->toDateTimeString(),
                $post->post_type,
                $post->like_count,
                $post->retweet_count,
                $post->reply_count,
                $post->view_count ?? 'NULL',
                $post->is_thread_post ? 'true' : 'false',
                $post->thread_position ?? 'NULL',
                $post->language_code ?? 'en',
                $post->created_at->toDateTimeString(),
                $post->updated_at->toDateTimeString()
            );
        }

        $sql .= implode(",\n", $values) . ";\n";

        return $sql;
    }

    private function escapeCsv(string $value): string
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    private function saveToFile(string $data, string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $data);
    }
}