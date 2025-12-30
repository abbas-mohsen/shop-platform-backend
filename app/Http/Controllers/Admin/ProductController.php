<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(12);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();

        // Clothing sizes (tops / pants)
        $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL'];

        // Shoes sizes 20–47
        $shoeSizes = range(20, 47);

        return view('admin.products.create', compact(
            'categories',
            'clothingSizes',
            'shoeSizes'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'sizes'       => ['nullable', 'array'],    // multiple sizes
            'sizes.*'     => ['string', 'max:10'],
        ]);

        $product = new Product();
        $product->name        = $data['name'];
        $product->category_id = $data['category_id'];
        $product->description = $data['description'] ?? null;
        $product->price       = $data['price'];
        $product->stock       = $data['stock'];

        // thanks to casts, this array becomes JSON in DB
        $product->sizes = $data['sizes'] ?? null;

        // handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = $path; // DB column is "image"
        }

        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $shoeSizes = range(20, 47);

        return view('admin.products.edit', compact(
            'product',
            'categories',
            'clothingSizes',
            'shoeSizes'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'sizes'       => ['nullable', 'array'],
            'sizes.*'     => ['string', 'max:10'],
        ]);

        $product->name        = $data['name'];
        $product->category_id = $data['category_id'];
        $product->description = $data['description'] ?? null;
        $product->price       = $data['price'];
        $product->stock       = $data['stock'];

        $product->sizes = $data['sizes'] ?? null;

        // new image (delete old one if exists)
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $path = $request->file('image')->store('products', 'public');
            $product->image = $path;
        }

        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
