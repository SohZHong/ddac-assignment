<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Blog extends Model
{
    use SoftDeletes, HasFactory;

    // Status Constants
    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1; 

    protected $fillable = [
        'title', 'slug', 'content', 'cover_image',
        'author_id', 'status', 'published_at'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Auto-generate slug if not set
    protected static function booted()
    {
        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }

    /**
     * Check if the blog has been published
    */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
