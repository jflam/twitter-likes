<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ThreadRelationship extends Model
{
    protected $table = 'thread_relationships';
    
    protected $keyType = 'string';
    public $incrementing = false;
    
    public $timestamps = false;

    protected $fillable = [
        'child_post_id',
        'parent_post_id', 
        'root_post_id',
        'depth_level',
        'relationship_type',
        'discovered_at',
    ];

    protected $casts = [
        'depth_level' => 'integer',
        'discovered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
            if (empty($model->discovered_at)) {
                $model->discovered_at = now();
            }
        });
    }

    public function childPost(): BelongsTo
    {
        return $this->belongsTo(LikedPost::class, 'child_post_id');
    }

    public function parentPost(): BelongsTo
    {
        return $this->belongsTo(LikedPost::class, 'parent_post_id');
    }

    public function rootPost(): BelongsTo
    {
        return $this->belongsTo(LikedPost::class, 'root_post_id');
    }

    public function scopeByRelationshipType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    public function scopeByDepth($query, int $depth)
    {
        return $query->where('depth_level', $depth);
    }

    public function scopeInThread($query, string $rootPostId)
    {
        return $query->where('root_post_id', $rootPostId);
    }

    public function isRootPost(): bool
    {
        return $this->depth_level === 0 && $this->parent_post_id === null;
    }
}