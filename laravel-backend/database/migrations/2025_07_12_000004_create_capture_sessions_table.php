<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('capture_sessions', function (Blueprint $table) {
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

    public function down()
    {
        Schema::dropIfExists('capture_sessions');
    }
};