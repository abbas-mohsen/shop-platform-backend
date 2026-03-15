<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function create(array $data, $imageFile = null): Product
    {
        if ($imageFile) {
            $data['image'] = $imageFile->store('products', 'public');
        }

        $data['sizes']         = $data['sizes'] ?? [];
        $data['color_options'] = $this->parseColorOptions($data['color_options'] ?? null);

        $colorsStock = $this->buildColorsStockArray(
            $data['colors_stock'] ?? null,
            $data['sizes'],
            $data['color_options'] ?? []
        );
        $data['colors_stock'] = $colorsStock;

        if ($colorsStock) {
            $data['sizes_stock'] = $this->deriveSizesStockFromColors($colorsStock);
        } else {
            $data['sizes_stock'] = $this->buildSizesStockArray(
                $data['sizes_stock'] ?? null,
                $data['sizes']
            );
        }

        if (empty($data['compare_at_price'])) {
            $data['compare_at_price'] = null;
        }

        return Product::create($data);
    }

    public function update(Product $product, array $data, $imageFile = null): Product
    {
        if ($imageFile) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $imageFile->store('products', 'public');
        }

        $data['sizes']         = $data['sizes'] ?? [];
        $data['color_options'] = $this->parseColorOptions($data['color_options'] ?? null);

        $colorsStock = $this->buildColorsStockArray(
            $data['colors_stock'] ?? null,
            $data['sizes'],
            $data['color_options'] ?? []
        );
        $data['colors_stock'] = $colorsStock;

        if ($colorsStock) {
            $data['sizes_stock'] = $this->deriveSizesStockFromColors($colorsStock);
        } else {
            $data['sizes_stock'] = $this->buildSizesStockArray(
                $data['sizes_stock'] ?? null,
                $data['sizes']
            );
        }

        if (empty($data['compare_at_price'])) {
            $data['compare_at_price'] = null;
        }

        $product->update($data);

        return $product;
    }

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

    public function parseColorOptions($raw): ?array
    {
        if (is_array($raw)) {
            $colors = array_values(array_filter(array_map('trim', $raw)));
            return !empty($colors) ? $colors : null;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $colors = array_values(array_filter(array_map('trim', explode(',', $raw))));
            return !empty($colors) ? $colors : null;
        }

        return null;
    }

    public function buildSizesStockArray($rawSizesStock, array $sizes): ?array
    {
        if (empty($sizes)) {
            return null;
        }

        if (is_string($rawSizesStock)) {
            $decoded = json_decode($rawSizesStock, true);
            $rawSizesStock = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
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

    public function buildColorsStockArray($rawColorsStock, array $sizes, ?array $colors): ?array
    {
        if (empty($sizes) || empty($colors)) {
            return null;
        }

        if (is_string($rawColorsStock)) {
            $decoded = json_decode($rawColorsStock, true);
            $rawColorsStock = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        if (!is_array($rawColorsStock)) {
            return null;
        }

        $result = [];
        foreach ($sizes as $size) {
            if (isset($rawColorsStock[$size]) && is_array($rawColorsStock[$size])) {
                foreach ($colors as $color) {
                    if (array_key_exists($color, $rawColorsStock[$size])) {
                        $result[$size][$color] = max(0, (int) $rawColorsStock[$size][$color]);
                    }
                }
            }
        }

        return !empty($result) ? $result : null;
    }

    private function deriveSizesStockFromColors(array $colorsStock): array
    {
        $result = [];
        foreach ($colorsStock as $size => $colorMap) {
            if (is_array($colorMap)) {
                $result[$size] = (int) array_sum($colorMap);
            }
        }
        return $result;
    }
}
