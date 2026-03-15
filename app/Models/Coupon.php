<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value'   => 'float',
        'min_order_amount' => 'float',
        'max_uses'         => 'integer',
        'used_count'       => 'integer',
        'is_active'        => 'boolean',
        'expires_at'       => 'datetime',
    ];

    /**
     * Check if the coupon is currently usable for a given order total.
     */
    public function isValidFor(float $orderTotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if (! is_null($this->max_uses) && $this->used_count >= $this->max_uses) {
            return false;
        }

        if (! is_null($this->min_order_amount) && $orderTotal < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for a given order total.
     */
    public function calculateDiscount(float $orderTotal): float
    {
        if ($this->discount_type === 'percentage') {
            return round($orderTotal * ($this->discount_value / 100), 2);
        }

        return min(round($this->discount_value, 2), $orderTotal);
    }
}
