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
     * POST /api/admin/products/bulk-sale
     * Body: { discount_percent: 20 }
     *
     * For every product that is NOT already on sale (compare_at_price is null),
     * save the original price in compare_at_price and apply the discount to price.
     */
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

    /**
     * DELETE /api/admin/products/bulk-sale
     *
     * Restores original prices for all products that have a compare_at_price set.
     */
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
