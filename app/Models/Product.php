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
        'sizes_stock',
    ];

    protected $casts = [
        'sizes' => 'array',
        'sizes_stock' => 'array',
    ];

    public function getAvailableSizesAttribute(): array
    {
        $raw = $this->attributes['sizes'] ?? null;

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $parts = array_map('trim', explode(',', $raw));
        } elseif (is_array($raw)) {
            $parts = $raw;
        } else {
            return [];
        }

        $parts = array_unique(array_filter($parts, fn ($v) => $v !== ''));

        return array_values($parts);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
