<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('thread_relationships', function (Blueprint $table) {
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

    public function down()
    {
        Schema::dropIfExists('thread_relationships');
    }
};