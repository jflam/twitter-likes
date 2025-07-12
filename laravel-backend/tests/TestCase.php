<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    protected static $capsule;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Set up database connection for testing
        self::$capsule = new Capsule;
        self::$capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:', // Use in-memory database for tests
            'prefix' => '',
            'foreign_key_constraints' => true, // Enable foreign key constraints
        ]);

        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();
        
        // Enable foreign key constraints for SQLite
        self::$capsule->getConnection()->statement('PRAGMA foreign_keys = ON');
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for each test
        $this->runMigrations();
    }

    protected function runMigrations()
    {
        $schema = self::$capsule->schema();
        
        // Create liked_posts table
        if (!$schema->hasTable('liked_posts')) {
            $schema->create('liked_posts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tweet_id')->unique();
                $table->string('author_username');
                $table->string('author_display_name');
                $table->string('author_avatar_url')->nullable();
                $table->text('content_text');
                $table->text('content_html')->nullable();
                $table->string('language_code')->nullable();
                $table->string('post_url');
                $table->timestamp('posted_at');
                $table->timestamp('liked_at');
                $table->timestamp('captured_at')->useCurrent();
                $table->enum('post_type', ['original', 'retweet', 'quote_tweet', 'reply']);
                $table->integer('reply_count')->default(0);
                $table->integer('retweet_count')->default(0);
                $table->integer('like_count')->default(0);
                $table->integer('view_count')->nullable();
                $table->boolean('is_thread_post')->default(false);
                $table->integer('thread_position')->nullable();
                $table->timestamps();

                // Indexes for performance
                $table->index('liked_at');
                $table->index('author_username');
                $table->index(['author_username', 'liked_at']);
                $table->index(['post_type', 'liked_at']);
                $table->index(['is_thread_post', 'thread_position']);
            });
        }

        // Create post_screenshots table
        if (!$schema->hasTable('post_screenshots')) {
            $schema->create('post_screenshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('liked_post_id');
                $table->string('image_path');
                $table->enum('image_format', ['png', 'jpg', 'jpeg', 'webp']);
                $table->integer('image_size_bytes');
                $table->integer('screenshot_width');
                $table->integer('screenshot_height');
                $table->enum('capture_method', ['full_post', 'visible_area', 'thread_context']);
                $table->float('quality_score')->nullable();
                $table->timestamps();

                // Foreign key relationship
                $table->foreign('liked_post_id')
                      ->references('id')
                      ->on('liked_posts')
                      ->onDelete('cascade');

                // Index for performance
                $table->index('liked_post_id');
                
                // Unique constraint - one screenshot per post
                $table->unique('liked_post_id');
            });
        }

        // Create thread_relationships table
        if (!$schema->hasTable('thread_relationships')) {
            $schema->create('thread_relationships', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('child_post_id');
                $table->uuid('parent_post_id')->nullable();
                $table->uuid('root_post_id');
                $table->integer('depth_level')->default(0);
                $table->enum('relationship_type', ['reply', 'quote', 'thread_continuation']);
                $table->timestamp('discovered_at')->useCurrent();

                // Foreign key relationships
                $table->foreign('child_post_id')
                      ->references('id')
                      ->on('liked_posts')
                      ->onDelete('cascade');
                
                $table->foreign('parent_post_id')
                      ->references('id')
                      ->on('liked_posts')
                      ->onDelete('cascade');
                
                $table->foreign('root_post_id')
                      ->references('id')
                      ->on('liked_posts')
                      ->onDelete('cascade');

                // Performance indexes
                $table->index('child_post_id');
                $table->index('parent_post_id');
                $table->index('root_post_id');
                
                // Unique constraint - prevent duplicate relationships
                $table->unique(['child_post_id', 'parent_post_id']);
            });
        }

        // Create capture_sessions table
        if (!$schema->hasTable('capture_sessions')) {
            $schema->create('capture_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('browser_session_id');
                $table->timestamp('capture_started_at');
                $table->timestamp('capture_completed_at')->nullable();
                $table->integer('posts_captured')->default(0);
                $table->integer('screenshots_captured')->default(0);
                $table->json('errors_encountered')->nullable();
                $table->string('x_com_page_url');
                $table->string('extension_version');
                $table->timestamps();

                // Performance indexes
                $table->index('browser_session_id');
                $table->index('capture_started_at');
            });
        }
    }

    protected function assertDatabaseHas(string $table, array $data): void
    {
        $query = self::$capsule->table($table);
        
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }
        
        $this->assertTrue($query->exists(), "Failed asserting that table '$table' contains " . json_encode($data));
    }

    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $query = self::$capsule->table($table);
        
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }
        
        $this->assertFalse($query->exists(), "Failed asserting that table '$table' does not contain " . json_encode($data));
    }
}