<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;

class SimpleApiTest extends TestCase
{
    public function test_post_controller_exists()
    {
        $this->assertTrue(class_exists('App\Http\Controllers\PostController'));
    }

    public function test_liked_post_model_exists()
    {
        $this->assertTrue(class_exists('App\Models\LikedPost'));
    }

    public function test_post_screenshot_model_exists()
    {
        $this->assertTrue(class_exists('App\Models\PostScreenshot'));
    }

    public function test_thread_relationship_model_exists()
    {
        $this->assertTrue(class_exists('App\Models\ThreadRelationship'));
    }

    public function test_capture_session_model_exists()
    {
        $this->assertTrue(class_exists('App\Models\CaptureSession'));
    }

    public function test_eloquent_models_have_correct_methods()
    {
        // Test that our models extend Eloquent properly
        $likedPost = new \App\Models\LikedPost();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $likedPost);
        
        $screenshot = new \App\Models\PostScreenshot();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $screenshot);
        
        $relationship = new \App\Models\ThreadRelationship();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $relationship);
        
        $session = new \App\Models\CaptureSession();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $session);
    }
}