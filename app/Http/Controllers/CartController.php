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
        // Allowed sizes for THIS product
        $availableSizes = $product->sizes_list;   // <- accessor from Product model

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'size'     => [
                'nullable',
                function ($attribute, $value, $fail) use ($availableSizes) {
                    // If the product has sizes configured, user must pick one of them
                    if (!empty($availableSizes)) {
                        if (!$value) {
                            return $fail('Please choose a size.');
                        }
                        if (!in_array($value, $availableSizes)) {
                            return $fail('Selected size is not available for this product.');
                        }
                    }
                },
            ],
        ]);

        $cart = session()->get('cart', []);

        $productId      = $product->id;
        $size           = $request->input('size');
        $quantityToAdd  = max(1, (int) $request->input('quantity', 1));
        $currentQty     = $cart[$productId]['quantity'] ?? 0;

        $cart[$productId] = [
            'id'       => $product->id,
            'name'     => $product->name,
            'price'    => $product->price,
            'image'    => $product->image,
            'size'     => $size,
            'sizes'    => $availableSizes,  // store allowed sizes in cart item
            'quantity' => $currentQty + $quantityToAdd,
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
        $availableSizes = $product->sizes_list;

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'size'     => [
                'nullable',
                function ($attribute, $value, $fail) use ($availableSizes) {
                    if (!empty($availableSizes)) {
                        if (!$value) {
                            return $fail('Please choose a size.');
                        }
                        if (!in_array($value, $availableSizes)) {
                            return $fail('Selected size is not available for this product.');
                        }
                    }
                },
            ],
        ]);

        $cart[$productId]['quantity'] = (int) $request->input('quantity');
        $cart[$productId]['size']     = $request->input('size');
        $cart[$productId]['sizes']    = $availableSizes;

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
