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

        
        $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL'];

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
            'sizes'       => ['nullable', 'array'],  
            'sizes.*'     => ['string', 'max:10'],
        ]);

        $product = new Product();
        $product->name        = $data['name'];
        $product->category_id = $data['category_id'];
        $product->description = $data['description'] ?? null;
        $product->price       = $data['price'];
        $product->stock       = $data['stock'];
        $product->sizes = $data['sizes'] ?? null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', config('filesystems.media_disk'));
            $product->image = $path;
        }

        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
{
    $categories    = Category::all();
    $clothingSizes = ['S', 'M', 'L', 'XL', 'XXL'];
    $shoeSizes     = range(20, 47);

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

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk(config('filesystems.media_disk'))->delete($product->image);
            }

            $path = $request->file('image')->store('products', config('filesystems.media_disk'));
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
            Storage::disk(config('filesystems.media_disk'))->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
