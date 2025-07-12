<?php

namespace Database\Factories;

use App\Models\LikedPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikedPostFactory extends Factory
{
    protected $model = LikedPost::class;

    public function definition()
    {
        $postedAt = $this->faker->dateTimeBetween('-1 month', '-1 hour');
        $likedAt = $this->faker->dateTimeBetween($postedAt, 'now');

        return [
            'tweet_id' => $this->faker->numerify('################'), // 16 digit number as string
            'author_username' => $this->faker->userName,
            'author_display_name' => $this->faker->name,
            'author_avatar_url' => $this->faker->imageUrl(128, 128, 'people'),
            'content_text' => $this->faker->sentence(rand(5, 20)),
            'content_html' => '<p>' . $this->faker->sentence(rand(5, 20)) . '</p>',
            'language_code' => $this->faker->randomElement(['en', 'es', 'fr', 'de', 'pt']),
            'post_url' => function (array $attributes) {
                return "https://x.com/{$attributes['author_username']}/status/{$attributes['tweet_id']}";
            },
            'posted_at' => $postedAt,
            'liked_at' => $likedAt,
            'captured_at' => $likedAt,
            'post_type' => $this->faker->randomElement(['original', 'retweet', 'quote_tweet', 'reply']),
            'reply_count' => $this->faker->numberBetween(0, 100),
            'retweet_count' => $this->faker->numberBetween(0, 500),
            'like_count' => $this->faker->numberBetween(0, 1000),
            'view_count' => $this->faker->optional(0.7)->numberBetween(100, 10000),
            'is_thread_post' => $this->faker->boolean(0.2),
            'thread_position' => function (array $attributes) {
                return $attributes['is_thread_post'] ? $this->faker->numberBetween(1, 5) : null;
            },
        ];
    }

    public function original()
    {
        return $this->state(function (array $attributes) {
            return [
                'post_type' => 'original',
                'is_thread_post' => false,
                'thread_position' => null,
            ];
        });
    }

    public function reply()
    {
        return $this->state(function (array $attributes) {
            return [
                'post_type' => 'reply',
                'is_thread_post' => true,
                'thread_position' => $this->faker->numberBetween(2, 10),
            ];
        });
    }

    public function highEngagement()
    {
        return $this->state(function (array $attributes) {
            return [
                'reply_count' => $this->faker->numberBetween(50, 200),
                'retweet_count' => $this->faker->numberBetween(100, 1000),
                'like_count' => $this->faker->numberBetween(500, 5000),
                'view_count' => $this->faker->numberBetween(10000, 100000),
            ];
        });
    }

    public function withScreenshot()
    {
        return $this->has(
            \Database\Factories\PostScreenshotFactory::new(),
            'screenshot'
        );
    }
}