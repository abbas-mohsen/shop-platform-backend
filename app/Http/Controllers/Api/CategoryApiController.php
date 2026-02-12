<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryApiController extends Controller
{
    public function index()
    {
        // Cache categories for 1 hour — they rarely change
        $categories = Cache::remember('categories:all', 3600, function () {
            return Category::all();
        });

        return CategoryResource::collection($categories);
    }
}
