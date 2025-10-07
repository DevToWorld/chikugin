<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'category_id',
        'featured_image',
        'is_important',
        'is_published',
        'is_featured',
        'view_count',
        'tags',
        'meta_description',
        'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'is_important' => 'boolean',
        'view_count' => 'integer'
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    // Scopes - CMS版newsテーブル構造に対応
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category_id', $category);
    }

    public function scopeForMembership($query, $membershipLevel = 'none')
    {
        // CMS版newsテーブルにはmembership_requirementフィールドがないので
        // 全てのお知らせを返す（会員制限なし）
        return $query;
    }

    // Accessors
    public function getFormattedDateAttribute()
    {
        return $this->published_at ? $this->published_at->format('Y.m.d') : '';
    }

    public function getDescriptionAttribute()
    {
        // excerpt フィールドがある場合はそれを返し、なければcontentから抜粋を作成
        if ($this->excerpt) {
            return $this->excerpt;
        }
        return strlen($this->content) > 100 
            ? substr($this->content, 0, 100) . '...' 
            : $this->content;
    }
}