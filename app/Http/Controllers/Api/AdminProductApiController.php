<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class AdminProductApiController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $products = Product::with('category')
            ->withCount('reviews')
            ->withCount('orderItems')
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load('category');

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request)
    {
        $data    = $request->validated();
        $product = $this->productService->create($data, $request->file('image'));

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data    = $request->validated();
        $product = $this->productService->update($product, $data, $request->file('image'));

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return response()->json(['message' => 'deleted']);
    }

    /**
     * Delete several products in one request. Doing it server-side avoids the
     * dropped/raced requests that firing many parallel DELETEs can cause.
     */
    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        $products = Product::whereIn('id', $data['ids'])->get();
        foreach ($products as $product) {
            $this->productService->delete($product);
        }

        return response()->json([
            'message'       => "{$products->count()} products deleted.",
            'deleted_count' => $products->count(),
        ]);
    }

    public function bulkSale(Request $request)
    {
        $request->validate([
            'discount_percent' => 'required|numeric|min:1|max:99',
        ]);

        $discount = (float) $request->input('discount_percent');
        $multiplier = 1 - ($discount / 100);

        $updated = Product::whereNull('compare_at_price')->get();

        foreach ($updated as $product) {
            $product->compare_at_price = $product->price;
            $product->price = round($product->price * $multiplier, 2);
            $product->save();
        }

        return response()->json([
            'message' => "Sale applied to {$updated->count()} products.",
            'updated_count' => $updated->count(),
        ]);
    }

    public function clearSale()
    {
        $updated = Product::whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price')
            ->get();

        foreach ($updated as $product) {
            $product->price = $product->compare_at_price;
            $product->compare_at_price = null;
            $product->save();
        }

        return response()->json([
            'message' => "Sale cleared from {$updated->count()} products.",
            'updated_count' => $updated->count(),
        ]);
    }
}
