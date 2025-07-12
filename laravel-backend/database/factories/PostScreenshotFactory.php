<?php

namespace Database\Factories;

use App\Models\PostScreenshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostScreenshotFactory extends Factory
{
    protected $model = PostScreenshot::class;

    public function definition()
    {
        $width = $this->faker->numberBetween(400, 800);
        $height = $this->faker->numberBetween(200, 600);
        $format = $this->faker->randomElement(['png', 'jpg', 'webp']);

        return [
            'image_path' => "screenshots/{$this->faker->uuid}.{$format}",
            'image_format' => $format,
            'image_size_bytes' => $this->faker->numberBetween(50000, 500000),
            'screenshot_width' => $width,
            'screenshot_height' => $height,
            'capture_method' => $this->faker->randomElement(['full_post', 'visible_area', 'thread_context']),
            'quality_score' => $this->faker->randomFloat(2, 0.7, 1.0),
        ];
    }

    public function highQuality()
    {
        return $this->state(function (array $attributes) {
            return [
                'quality_score' => $this->faker->randomFloat(2, 0.9, 1.0),
                'image_format' => 'png',
                'capture_method' => 'full_post',
            ];
        });
    }

    public function mobile()
    {
        return $this->state(function (array $attributes) {
            return [
                'screenshot_width' => $this->faker->numberBetween(300, 400),
                'screenshot_height' => $this->faker->numberBetween(500, 800),
                'capture_method' => 'visible_area',
            ];
        });
    }
}