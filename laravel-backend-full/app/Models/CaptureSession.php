<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaptureSession extends Model
{
    protected $table = 'capture_sessions';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'browser_session_id',
        'capture_started_at',
        'capture_completed_at',
        'posts_captured',
        'screenshots_captured',
        'errors_encountered',
        'x_com_page_url',
        'extension_version',
    ];

    protected $casts = [
        'capture_started_at' => 'datetime',
        'capture_completed_at' => 'datetime',
        'posts_captured' => 'integer',
        'screenshots_captured' => 'integer',
        'errors_encountered' => 'array',
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

    public function getDurationInSeconds(): ?int
    {
        if (!$this->capture_completed_at) {
            return null;
        }
        
        return $this->capture_completed_at->diffInSeconds($this->capture_started_at);
    }

    public function isCompleted(): bool
    {
        return $this->capture_completed_at !== null;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors_encountered);
    }

    public function getSuccessRate(): float
    {
        if ($this->posts_captured === 0) {
            return 0;
        }
        
        $errors = $this->hasErrors() ? count($this->errors_encountered) : 0;
        return round(($this->posts_captured - $errors) / $this->posts_captured * 100, 2);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('capture_completed_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('capture_completed_at');
    }

    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('errors_encountered');
    }
}