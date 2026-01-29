<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductApiController extends Controller
{
    public function index()
    {
        // You can use pagination later, for now keep it simple
        $products = Product::with('category')->get();

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load('category');

        // Make sure sizes is decoded as array if stored as JSON / string.
        // If you're storing sizes as JSON in DB, you can also cast it in the model.
        return response()->json($product);
    }
}
