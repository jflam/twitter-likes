<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PostScreenshot extends Model
{
    protected $table = 'post_screenshots';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'liked_post_id',
        'image_path',
        'image_format',
        'image_size_bytes',
        'screenshot_width',
        'screenshot_height',
        'capture_method',
        'quality_score',
    ];

    protected $casts = [
        'image_size_bytes' => 'integer',
        'screenshot_width' => 'integer',
        'screenshot_height' => 'integer',
        'quality_score' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    public function likedPost(): BelongsTo
    {
        return $this->belongsTo(LikedPost::class, 'liked_post_id');
    }

    public function getImageSizeInMB(): float
    {
        return round($this->image_size_bytes / 1024 / 1024, 2);
    }

    public function getAspectRatio(): float
    {
        if ($this->screenshot_height == 0) {
            return 0;
        }
        return round($this->screenshot_width / $this->screenshot_height, 2);
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('image_format', $format);
    }

    public function scopeByQuality($query, float $minQuality)
    {
        return $query->where('quality_score', '>=', $minQuality);
    }
}