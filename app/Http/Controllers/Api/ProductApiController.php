<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * GET /api/products
     *
     * Query parameters:
     *   q            - Search by name or description (partial match)
     *   category_id  - Filter by category ID
     *   category     - Filter by category name (partial match)
     *   min_price    - Minimum price
     *   max_price    - Maximum price
     *   sort         - Sort field: price, name, created_at, rating (default: created_at)
     *   order        - Sort direction: asc, desc (default: desc)
     *   per_page     - Results per page (default: 12, max: 100)
     *   on_sale      - 1 = only products with compare_at_price > price
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 12), 100);

        $query = Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        // Search by name or description
        if ($search = trim($request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by category ID (single value or array via category_id[])
        if ($categoryId = $request->query('category_id')) {
            $ids = is_array($categoryId) ? $categoryId : [$categoryId];
            $query->whereIn('category_id', $ids);
        }

        // Filter by category name
        if ($categoryName = trim($request->query('category', ''))) {
            $query->whereHas('category', function ($q) use ($categoryName) {
                $q->where('name', 'like', '%' . $categoryName . '%');
            });
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->query('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }

        // In-stock only
        if ($request->query('in_stock') === 'true') {
            $query->where('stock', '>', 0);
        }

        // On-sale filter (compare_at_price > price)
        if ($request->query('on_sale') === '1') {
            $query->whereNotNull('compare_at_price')
                  ->whereColumn('compare_at_price', '>', 'price');
        }

        // Sorting
        $sortField = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');

        $allowedSorts = ['price', 'name', 'created_at', 'stock'];
        $sortField = in_array($sortField, $allowedSorts) ? $sortField : 'created_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        if ($sortField === 'rating') {
            $query->orderBy('reviews_avg_rating', $sortOrder);
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $products = $query->paginate($perPage)->withQueryString();

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
