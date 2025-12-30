<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category_id',
        'image',
        'sizes',
    ];

    protected $casts = [
        'sizes' => 'array',
    ];

    public function getAvailableSizesAttribute(): array
    {
        $raw = $this->attributes['sizes'] ?? null;

        if ($raw === null || $raw === '') {
            return [];
        }

        // If the DB still has old comma-separated strings, handle them:
        if (is_string($raw)) {
            $parts = array_map('trim', explode(',', $raw));
        } elseif (is_array($raw)) {
            $parts = $raw;
        } else {
            return [];
        }

        // Remove empty duplicates and re-index.
        $parts = array_unique(array_filter($parts, fn ($v) => $v !== ''));

        return array_values($parts);
    }

    // Each product belongs to one category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
