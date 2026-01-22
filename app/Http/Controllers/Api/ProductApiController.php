<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductApiController extends Controller
{
    public function index()
    {
        // Return products with category & sizes
        $products = Product::with('category')->latest()->get();

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load('category');

        return response()->json($product);
    }
}
