<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'size',
        'color',
        'quantity',
        'unit_price',
        'line_total',
    ];

    /**
     * Human-readable colour name (never the raw hex) for display in emails,
     * invoices and the UI.
     */
    public function getColorNameAttribute(): ?string
    {
        return \App\Support\Color::name($this->color);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
