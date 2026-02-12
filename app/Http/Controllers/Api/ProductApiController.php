<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 12), 100); // Cap max per_page

        $products = Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load('category');
        $product->loadCount('reviews');
        $product->loadAvg('reviews', 'rating');

        return new ProductResource($product);
    }
}
