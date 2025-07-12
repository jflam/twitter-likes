<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LikedPost extends Model
{
    protected $table = 'liked_posts';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tweet_id',
        'author_username',
        'author_display_name',
        'author_avatar_url',
        'content_text',
        'content_html',
        'language_code',
        'post_url',
        'posted_at',
        'liked_at',
        'captured_at',
        'post_type',
        'reply_count',
        'retweet_count',
        'like_count',
        'view_count',
        'is_thread_post',
        'thread_position',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'liked_at' => 'datetime', 
        'captured_at' => 'datetime',
        'is_thread_post' => 'boolean',
        'reply_count' => 'integer',
        'retweet_count' => 'integer',
        'like_count' => 'integer',
        'view_count' => 'integer',
        'thread_position' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
            if (empty($model->captured_at)) {
                $model->captured_at = now();
            }
        });
    }

    public function screenshot(): HasOne
    {
        return $this->hasOne(PostScreenshot::class, 'liked_post_id');
    }

    public function childRelationships(): HasMany
    {
        return $this->hasMany(ThreadRelationship::class, 'parent_post_id');
    }

    public function parentRelationship(): HasOne
    {
        return $this->hasOne(ThreadRelationship::class, 'child_post_id');
    }

    public function rootRelationships(): HasMany
    {
        return $this->hasMany(ThreadRelationship::class, 'root_post_id');
    }

    public function scopeWithThreadContext($query)
    {
        return $query->with(['parentRelationship', 'childRelationships', 'rootRelationships']);
    }

    public function scopeByAuthor($query, string $username)
    {
        return $query->where('author_username', $username);
    }

    public function scopeByPostType($query, string $type)
    {
        return $query->where('post_type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('liked_at', [$startDate, $endDate]);
    }
}