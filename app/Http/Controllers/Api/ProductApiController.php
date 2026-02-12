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
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 12), 100);

        $query = Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        // Search by name or description
        if ($search = trim($request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by category ID
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
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

        // Sorting
        $sortField = $request->query('sort', 'created_at');
        $sortOrder = $request->query('order', 'desc');

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
