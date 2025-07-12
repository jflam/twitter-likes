<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('liked_posts', function (Blueprint $table) {
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

    public function down()
    {
        Schema::dropIfExists('liked_posts');
    }
};