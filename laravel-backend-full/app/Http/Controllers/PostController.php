<?php

namespace App\Http\Controllers;

use App\Models\LikedPost;
use App\Models\PostScreenshot;
use App\Models\ThreadRelationship;
use App\Models\CaptureSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function capture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tweet_id' => 'required|string|unique:liked_posts,tweet_id',
            'author_username' => 'required|string',
            'author_display_name' => 'required|string',
            'author_avatar_url' => 'nullable|string|url',
            'content_text' => 'required|string',
            'content_html' => 'nullable|string',
            'post_url' => 'required|string|url',
            'posted_at' => 'required|date',
            'liked_at' => 'required|date|after_or_equal:posted_at',
            'post_type' => 'required|in:original,retweet,quote_tweet,reply',
            'like_count' => 'integer|min:0',
            'retweet_count' => 'integer|min:0',
            'reply_count' => 'integer|min:0',
            'view_count' => 'nullable|integer|min:0',
            'screenshot_base64' => 'nullable|string',
            'screenshot_width' => 'nullable|integer|min:1',
            'screenshot_height' => 'nullable|integer|min:1',
            'thread_context' => 'nullable|array',
            'thread_context.*.tweet_id' => 'required_with:thread_context|string',
            'thread_context.*.content_text' => 'required_with:thread_context|string',
            'thread_context.*.author_username' => 'required_with:thread_context|string',
            'thread_context.*.relationship' => 'required_with:thread_context|in:parent,root,child',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'error_code' => 'INVALID_REQUEST',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $postData = $request->only([
                'tweet_id', 'author_username', 'author_display_name', 'author_avatar_url',
                'content_text', 'content_html', 'post_url', 'posted_at', 'liked_at',
                'post_type', 'reply_count', 'retweet_count', 'like_count', 'view_count'
            ]);

            $likedPost = LikedPost::create($postData);

            $screenshotSaved = false;
            if ($request->has('screenshot_base64') && !empty($request->screenshot_base64)) {
                $screenshotSaved = $this->saveScreenshot($likedPost, $request);
            }

            $threadRelationshipsCreated = 0;
            if ($request->has('thread_context') && is_array($request->thread_context)) {
                $threadRelationshipsCreated = $this->createThreadRelationships($likedPost, $request->thread_context);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'post_id' => $likedPost->id,
                'screenshot_saved' => $screenshotSaved,
                'thread_relationships_created' => $threadRelationshipsCreated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to capture post',
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }

    public function unlike(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tweet_id' => 'required|string|exists:liked_posts,tweet_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Post not found',
                'error_code' => 'POST_NOT_FOUND'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $likedPost = LikedPost::where('tweet_id', $request->tweet_id)->firstOrFail();
            
            $screenshotRemoved = false;
            if ($likedPost->screenshot) {
                Storage::delete($likedPost->screenshot->image_path);
                $screenshotRemoved = true;
            }

            $likedPost->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'deleted' => true,
                'screenshot_removed' => $screenshotRemoved
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete post',
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }

    public function status()
    {
        try {
            $stats = [
                'total_posts' => LikedPost::count(),
                'unprocessed_posts' => LikedPost::whereNull('updated_at')->count(),
                'screenshot_count' => PostScreenshot::count(),
                'thread_relationships' => ThreadRelationship::count(),
                'last_capture' => LikedPost::latest('captured_at')->value('captured_at')
            ];

            return response()->json([
                'status' => 'success',
                'stats' => $stats,
                'server_status' => 'healthy'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get status',
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }

    private function saveScreenshot(LikedPost $likedPost, Request $request): bool
    {
        try {
            if (!$request->has('screenshot_base64') || empty($request->screenshot_base64)) {
                return false;
            }

            $imageData = base64_decode($request->screenshot_base64);
            if ($imageData === false) {
                return false;
            }

            $filename = 'screenshots/' . $likedPost->id . '.png';
            Storage::put($filename, $imageData);

            PostScreenshot::create([
                'liked_post_id' => $likedPost->id,
                'image_path' => $filename,
                'image_format' => 'png',
                'image_size_bytes' => strlen($imageData),
                'screenshot_width' => $request->screenshot_width ?? 0,
                'screenshot_height' => $request->screenshot_height ?? 0,
                'capture_method' => 'full_post',
                'quality_score' => 1.0
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    private function createThreadRelationships(LikedPost $likedPost, array $threadContext): int
    {
        $created = 0;
        
        try {
            foreach ($threadContext as $context) {
                $relationship = null;
                $depthLevel = 0;
                $parentPostId = null;
                $rootPostId = $likedPost->id;

                switch ($context['relationship']) {
                    case 'parent':
                        $relationship = 'reply';
                        $depthLevel = 1;
                        $parentPostId = $this->findOrCreateRelatedPost($context);
                        break;
                    case 'root':
                        $relationship = 'thread_continuation';
                        $depthLevel = 1;
                        $rootPostId = $this->findOrCreateRelatedPost($context);
                        break;
                    case 'child':
                        $relationship = 'reply';
                        $depthLevel = 0;
                        break;
                }

                if ($relationship) {
                    ThreadRelationship::create([
                        'child_post_id' => $likedPost->id,
                        'parent_post_id' => $parentPostId,
                        'root_post_id' => $rootPostId,
                        'depth_level' => $depthLevel,
                        'relationship_type' => $relationship
                    ]);
                    $created++;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the entire operation
        }

        return $created;
    }

    private function findOrCreateRelatedPost(array $context): ?string
    {
        $existingPost = LikedPost::where('tweet_id', $context['tweet_id'])->first();
        
        if ($existingPost) {
            return $existingPost->id;
        }

        return null;
    }
}