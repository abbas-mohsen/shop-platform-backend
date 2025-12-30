<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);

        $total = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return view('cart.index', [
            'cart'  => $cart,
            'total' => $total,
        ]);
    }

    public function add(Request $request, Product $product)
    {
        $Sizes = $product->sizes_array;

        // Validate size ONLY against this product's allowed sizes
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'size'     => [
                'nullable',
                function ($attribute, $value, $fail) use ($availableSizes) {
                    if (!empty($availableSizes)) {
                        if (!$value) {
                            $fail('Please choose a size.');
                        } elseif (!in_array($value, $availableSizes)) {
                            $fail('Selected size is not available for this product.');
                        }
                    }
                },
            ],
        ]);

        $cart = session()->get('cart', []);

        $productId = $product->id;
        $size = $request->input('size');

        // If item already exists, just increase quantity
        $quantityToAdd = (int) $request->input('quantity', 1);
        $currentQty    = isset($cart[$productId]) ? $cart[$productId]['quantity'] : 0;

        $cart[$productId] = [
            'id'              => $product->id,
            'name'            => $product->name,
            'price'           => $product->price,
            'image'           => $product->image,
            'size'            => $size,
            'sizes'           => $Sizes,
            'quantity'        => $currentQty + $quantityToAdd,
        ];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Product added to cart.');
    }

    public function update(Request $request, $productId)
    {
        $cart = session()->get('cart', []);

        if (!isset($cart[$productId])) {
            return redirect()->route('cart.index');
        }

        $product        = Product::findOrFail($productId);
        $Sizes = $product->sizes_array;

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'size'     => [
                'nullable',
                function ($attribute, $value, $fail) use ($availableSizes) {
                    if (!empty($availableSizes)) {
                        if (!$value) {
                            $fail('Please choose a size.');
                        } elseif (!in_array($value, $availableSizes)) {
                            $fail('Selected size is not available for this product.');
                        }
                    }
                },
            ],
        ]);

        $cart[$productId]['quantity']        = (int) $request->input('quantity');
        $cart[$productId]['size']            = $request->input('size');
        $cart[$productId]['sizes'] = $Sizes;

        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Cart updated.');
    }

    public function remove($productId)
    {
        $cart = session()->get('cart', []);
        unset($cart[$productId]);
        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('success', 'Product removed.');
    }
}
