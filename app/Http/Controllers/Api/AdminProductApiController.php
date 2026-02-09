<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProductApiController extends Controller
{
    // GET /api/admin/products
    public function index()
    {
        return Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // GET /api/admin/products/{product}
    public function show(Product $product)
    {
        $product->load('category');

        return response()->json($product);
    }

    // POST /api/admin/products
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('products', 'public');
        }

        // sizes come from form as array; store as array (cast => json)
        $data['sizes'] = $data['sizes'] ?? [];

        // handle sizes_stock (JSON string from React form)
        $data['sizes_stock'] = $this->buildSizesStockArray(
            $request->input('sizes_stock'),
            $data['sizes']
        );

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    // POST or PUT /api/admin/products/{product}
    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, $product->id);

        if ($request->hasFile('image')) {
            // delete old if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $data['sizes'] = $data['sizes'] ?? [];

        // handle sizes_stock (JSON string from React form)
        $data['sizes_stock'] = $this->buildSizesStockArray(
            $request->input('sizes_stock'),
            $data['sizes']
        );

        $product->update($data);

        return response()->json($product);
    }

    // DELETE /api/admin/products/{product}
    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * Common validation for create / update.
     */
    private function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            // stock is optional now (you can rely on per-size stock)
            'stock'       => ['nullable', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'sizes'       => ['nullable', 'array'],
            'sizes.*'     => ['string', 'max:10'],
            // sizes_stock comes as JSON string in FormData; we process it manually
            'sizes_stock' => ['nullable'],
        ]);
    }

    /**
     * Decode and clean sizes_stock based on the selected sizes.
     *
     * @param  mixed $rawSizesStock  JSON string or array from the request.
     * @param  array $sizes          Selected sizes from form (["S","M","L"]).
     * @return array|null
     */
    private function buildSizesStockArray($rawSizesStock, array $sizes): ?array
    {
        if (empty($sizes)) {
            return null;
        }

        // If it's a JSON string from FormData, decode it
        if (is_string($rawSizesStock)) {
            $decoded = json_decode($rawSizesStock, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rawSizesStock = $decoded;
            } else {
                $rawSizesStock = null;
            }
        }

        if (!is_array($rawSizesStock)) {
            return null;
        }

        // Keep only keys that exist in $sizes and normalize to int >= 0
        $result = [];
        foreach ($sizes as $size) {
            if (array_key_exists($size, $rawSizesStock)) {
                $qty = (int) $rawSizesStock[$size];
                $result[$size] = max(0, $qty);
            }
        }

        return !empty($result) ? $result : null;
    }
}
