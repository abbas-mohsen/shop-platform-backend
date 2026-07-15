<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'compare_at_price',
        'stock',
        'category_id',
        'image',
        'sizes',
        'sizes_stock',
        'color_options',
        'colors_stock',
        'embedding',
    ];

    protected $casts = [
        'sizes'           => 'array',
        'sizes_stock'     => 'array',
        'color_options'   => 'array',
        'colors_stock'    => 'array',
        'embedding'       => 'array',
    ];

    public function getAvailableSizesAttribute(): array
    {
        // Use the 'array' cast — the column stores JSON (e.g. ["S","M","L"]).
        // Splitting the raw attribute by comma mangles it into ['["S"', '"M"', ...].
        $sizes = $this->sizes;

        if (! is_array($sizes)) {
            // Legacy fallback: plain comma-separated string ("S,M,L") that
            // the JSON cast can't decode.
            $raw = $this->attributes['sizes'] ?? null;
            if (! is_string($raw) || $raw === '') {
                return [];
            }
            $sizes = array_map('trim', explode(',', $raw));
        }

        $sizes = array_unique(array_filter($sizes, fn ($v) => is_string($v) && $v !== ''));

        return array_values($sizes);
    }

    /**
     * Available stock for a specific size/color, using the same precedence as
     * checkout: colour-within-size stock, then per-size stock, then the
     * product-level stock. Returns null when no stock constraint is tracked.
     */
    public function availableStockFor(?string $size = null, ?string $color = null): ?int
    {
        $colorsStock = $this->colors_stock ?? [];
        if ($size && $color && is_array($colorsStock)
            && isset($colorsStock[$size]) && is_array($colorsStock[$size])
            && array_key_exists($color, $colorsStock[$size])) {
            return (int) $colorsStock[$size][$color];
        }

        $sizesStock = $this->sizes_stock ?? [];
        if ($size && is_array($sizesStock) && array_key_exists($size, $sizesStock)) {
            return (int) $sizesStock[$size];
        }

        return is_null($this->stock) ? null : (int) $this->stock;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
