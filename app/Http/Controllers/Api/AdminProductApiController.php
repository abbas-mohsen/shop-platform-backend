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

    private function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'sizes'       => ['nullable', 'array'],
            'sizes.*'     => ['string', 'max:10'],
        ]);
    }
}
