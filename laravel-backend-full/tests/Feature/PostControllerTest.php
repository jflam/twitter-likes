<?php

namespace Tests\Feature;

use App\Models\LikedPost;
use App\Models\PostScreenshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_returns_correct_format(): void
    {
        $response = $this->getJson('/api/posts/status');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'stats' => [
                         'total_posts',
                         'unprocessed_posts',
                         'screenshot_count',
                         'thread_relationships',
                         'last_capture'
                     ],
                     'server_status'
                 ]);
    }

    public function test_capture_endpoint_creates_new_post(): void
    {
        $tweetData = [
            'tweet_id' => '1234567890',
            'author_username' => 'testuser',
            'author_display_name' => 'Test User',
            'author_avatar_url' => 'https://example.com/avatar.jpg',
            'content_text' => 'This is a test tweet',
            'content_html' => '<p>This is a test tweet</p>',
            'language_code' => 'en',
            'post_url' => 'https://x.com/testuser/status/1234567890',
            'posted_at' => '2025-07-12T12:00:00Z',
            'liked_at' => '2025-07-12T12:05:00Z',
            'post_type' => 'original',
            'reply_count' => 5,
            'retweet_count' => 10,
            'like_count' => 25,
            'view_count' => 100,
            'is_thread_post' => false
        ];

        $response = $this->postJson('/api/posts/capture', $tweetData);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success'
                 ])
                 ->assertJsonStructure([
                     'status',
                     'post_id',
                     'screenshot_saved',
                     'thread_relationships_created'
                 ]);

        $this->assertDatabaseHas('liked_posts', [
            'tweet_id' => '1234567890',
            'author_username' => 'testuser',
            'content_text' => 'This is a test tweet'
        ]);
    }

    public function test_capture_endpoint_rejects_duplicate_tweet_id(): void
    {
        LikedPost::create([
            'id' => Str::uuid(),
            'tweet_id' => '1234567890',
            'author_username' => 'existing_user',
            'author_display_name' => 'Existing User',
            'content_text' => 'Existing tweet',
            'post_url' => 'https://x.com/existing_user/status/1234567890',
            'posted_at' => now()->subHour(),
            'liked_at' => now()->subMinutes(30),
            'post_type' => 'original'
        ]);

        $tweetData = [
            'tweet_id' => '1234567890',
            'author_username' => 'testuser2',
            'author_display_name' => 'Test User 2',
            'content_text' => 'Another test tweet',
            'post_url' => 'https://x.com/testuser2/status/1234567890',
            'posted_at' => '2025-07-12T12:00:00Z',
            'liked_at' => '2025-07-12T12:05:00Z',
            'post_type' => 'original'
        ];

        $response = $this->postJson('/api/posts/capture', $tweetData);

        $response->assertStatus(422)
                 ->assertJsonPath('details.tweet_id.0', 'The tweet id has already been taken.');
    }

    public function test_capture_endpoint_validates_required_fields(): void
    {
        $response = $this->postJson('/api/posts/capture', []);

        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Validation failed'
                 ])
                 ->assertJsonStructure([
                     'status',
                     'message', 
                     'error_code',
                     'details' => [
                         'tweet_id',
                         'author_username',
                         'author_display_name',
                         'content_text',
                         'post_url',
                         'posted_at',
                         'liked_at',
                         'post_type'
                     ]
                 ]);
    }

    public function test_capture_endpoint_validates_post_type_enum(): void
    {
        $tweetData = [
            'tweet_id' => '1234567890',
            'author_username' => 'testuser',
            'author_display_name' => 'Test User',
            'content_text' => 'This is a test tweet',
            'post_url' => 'https://x.com/testuser/status/1234567890',
            'posted_at' => '2025-07-12T12:00:00Z',
            'liked_at' => '2025-07-12T12:05:00Z',
            'post_type' => 'invalid_type'
        ];

        $response = $this->postJson('/api/posts/capture', $tweetData);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'details' => ['post_type']
                 ]);
    }

    public function test_unlike_endpoint_removes_existing_post(): void
    {
        $post = LikedPost::create([
            'id' => Str::uuid(),
            'tweet_id' => '1234567890',
            'author_username' => 'testuser',
            'author_display_name' => 'Test User',
            'content_text' => 'Test tweet to be removed',
            'post_url' => 'https://x.com/testuser/status/1234567890',
            'posted_at' => now()->subHour(),
            'liked_at' => now()->subMinutes(30),
            'post_type' => 'original'
        ]);

        $response = $this->deleteJson('/api/posts/unlike', [
            'tweet_id' => '1234567890'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'deleted' => true,
                     'screenshot_removed' => false
                 ]);

        $this->assertDatabaseMissing('liked_posts', [
            'tweet_id' => '1234567890'
        ]);
    }

    public function test_unlike_endpoint_handles_non_existent_post(): void
    {
        $response = $this->deleteJson('/api/posts/unlike', [
            'tweet_id' => 'non_existent_tweet'
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Post not found'
                 ]);
    }

    public function test_unlike_endpoint_validates_tweet_id(): void
    {
        $response = $this->deleteJson('/api/posts/unlike', []);

        $response->assertStatus(404)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Post not found',
                     'error_code' => 'POST_NOT_FOUND'
                 ]);
    }

    public function test_cors_headers_are_present(): void
    {
        $response = $this->get('/api/posts/status');

        $response->assertStatus(200)
                 ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:*')
                 ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                 ->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    }

    public function test_options_preflight_request(): void
    {
        $response = $this->options('/api/posts/capture');

        $response->assertStatus(200)
                 ->assertHeader('Allow', 'POST');
    }
}
