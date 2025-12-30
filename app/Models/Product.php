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

    public function getSizesArrayAttribute()
    {
        if (!$this->sizes) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->sizes)));
    }

    // Each product belongs to one category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
