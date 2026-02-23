<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /**
     * Create a new product, handling image upload and sizes_stock parsing.
     */
    public function create(array $data, $imageFile = null): Product
    {
        if ($imageFile) {
            $data['image'] = $imageFile->store('products', 'public');
        }

        $data['sizes']       = $data['sizes'] ?? [];
        $data['sizes_stock'] = $this->buildSizesStockArray(
            $data['sizes_stock'] ?? null,
            $data['sizes']
        );

        return Product::create($data);
    }

    /**
     * Update an existing product, handling image replacement.
     */
    public function update(Product $product, array $data, $imageFile = null): Product
    {
        if ($imageFile) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $imageFile->store('products', 'public');
        }

        $data['sizes']       = $data['sizes'] ?? [];
        $data['sizes_stock'] = $this->buildSizesStockArray(
            $data['sizes_stock'] ?? null,
            $data['sizes']
        );

        $product->update($data);

        return $product;
    }

    /**
     * Delete a product and its associated image.
     * Blocks deletion if the product is part of a pending or approved order.
     */
    public function delete(Product $product): void
    {
        $activeOrderExists = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
            ->exists();

        if ($activeOrderExists) {
            throw ValidationException::withMessages([
                'product' => ['This product cannot be deleted because it is part of an active order.'],
            ]);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
    }

    /**
     * Parse sizes_stock from a JSON string or array based on selected sizes.
     */
    public function buildSizesStockArray($rawSizesStock, array $sizes): ?array
    {
        if (empty($sizes)) {
            return null;
        }

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

        $result = [];
        foreach ($sizes as $size) {
            if (array_key_exists($size, $rawSizesStock)) {
                $result[$size] = max(0, (int) $rawSizesStock[$size]);
            }
        }

        return !empty($result) ? $result : null;
    }
}
