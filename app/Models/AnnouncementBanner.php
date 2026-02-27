<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementBanner extends Model
{
    protected $fillable = ['message', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
