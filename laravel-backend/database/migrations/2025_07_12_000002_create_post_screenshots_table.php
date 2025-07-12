<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_screenshots', function (Blueprint $table) {
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

    public function down()
    {
        Schema::dropIfExists('post_screenshots');
    }
};