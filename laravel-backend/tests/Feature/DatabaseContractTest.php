<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseContractTest extends TestCase
{
    public function test_liked_posts_table_exists_with_correct_structure()
    {
        $schema = Capsule::schema();
        
        $this->assertTrue($schema->hasTable('liked_posts'));
        
        $columns = [
            'id', 'tweet_id', 'author_username', 'author_display_name',
            'author_avatar_url', 'content_text', 'content_html', 'language_code',
            'post_url', 'posted_at', 'liked_at', 'captured_at', 'post_type',
            'reply_count', 'retweet_count', 'like_count', 'view_count',
            'is_thread_post', 'thread_position', 'created_at', 'updated_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue($schema->hasColumn('liked_posts', $column), "Column '$column' missing from liked_posts table");
        }
    }

    public function test_post_screenshots_table_exists_with_correct_structure()
    {
        $schema = Capsule::schema();
        
        $this->assertTrue($schema->hasTable('post_screenshots'));
        
        $columns = [
            'id', 'liked_post_id', 'image_path', 'image_format',
            'image_size_bytes', 'screenshot_width', 'screenshot_height',
            'capture_method', 'quality_score', 'created_at', 'updated_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue($schema->hasColumn('post_screenshots', $column), "Column '$column' missing from post_screenshots table");
        }
    }

    public function test_thread_relationships_table_exists_with_correct_structure()
    {
        $schema = Capsule::schema();
        
        $this->assertTrue($schema->hasTable('thread_relationships'));
        
        $columns = [
            'id', 'child_post_id', 'parent_post_id', 'root_post_id',
            'depth_level', 'relationship_type', 'discovered_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue($schema->hasColumn('thread_relationships', $column), "Column '$column' missing from thread_relationships table");
        }
    }

    public function test_capture_sessions_table_exists_with_correct_structure()
    {
        $schema = Capsule::schema();
        
        $this->assertTrue($schema->hasTable('capture_sessions'));
        
        $columns = [
            'id', 'browser_session_id', 'capture_started_at', 'capture_completed_at',
            'posts_captured', 'screenshots_captured', 'errors_encountered',
            'x_com_page_url', 'extension_version', 'created_at', 'updated_at'
        ];
        
        foreach ($columns as $column) {
            $this->assertTrue($schema->hasColumn('capture_sessions', $column), "Column '$column' missing from capture_sessions table");
        }
    }

    public function test_can_insert_basic_liked_post_data()
    {
        $data = [
            'id' => 'test-uuid-123',
            'tweet_id' => '1234567890123456789',
            'author_username' => 'testuser',
            'author_display_name' => 'Test User',
            'content_text' => 'This is a test tweet',
            'post_url' => 'https://x.com/testuser/status/1234567890123456789',
            'posted_at' => '2025-07-12 10:30:00',
            'liked_at' => '2025-07-12 14:30:00',
            'captured_at' => '2025-07-12 14:30:00',
            'post_type' => 'original',
            'reply_count' => 0,
            'retweet_count' => 0,
            'like_count' => 0,
            'is_thread_post' => false,
            'created_at' => '2025-07-12 14:30:00',
            'updated_at' => '2025-07-12 14:30:00'
        ];

        // Insert directly into database
        Capsule::table('liked_posts')->insert($data);

        // Verify it was inserted
        $this->assertDatabaseHas('liked_posts', [
            'tweet_id' => '1234567890123456789',
            'author_username' => 'testuser'
        ]);
    }

    public function test_foreign_key_constraint_prevents_orphaned_screenshots()
    {
        // This should fail because we're trying to insert a screenshot
        // with a liked_post_id that doesn't exist
        $this->expectException(\Exception::class);

        Capsule::table('post_screenshots')->insert([
            'id' => 'screenshot-uuid-123',
            'liked_post_id' => 'non-existent-post-id',
            'image_path' => '/path/to/image.png',
            'image_format' => 'png',
            'image_size_bytes' => 1024,
            'screenshot_width' => 600,
            'screenshot_height' => 400,
            'capture_method' => 'full_post',
            'created_at' => '2025-07-12 14:30:00',
            'updated_at' => '2025-07-12 14:30:00'
        ]);
    }
}