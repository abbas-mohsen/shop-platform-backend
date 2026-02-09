<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);

        $products = Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->paginate($perPage);

        // Round the avg rating
        $products->getCollection()->transform(function ($product) {
            $product->reviews_avg_rating = $product->reviews_avg_rating
                ? round((float) $product->reviews_avg_rating, 1)
                : null;
            return $product;
        });

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load('category');
        $product->loadCount('reviews');
        $product->loadAvg('reviews', 'rating');

        $product->reviews_avg_rating = $product->reviews_avg_rating
            ? round((float) $product->reviews_avg_rating, 1)
            : null;

        return response()->json($product);
    }
}
