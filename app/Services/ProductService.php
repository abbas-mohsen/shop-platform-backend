<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    private EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    public function create(array $data, $imageFile = null): Product
    {
        if ($imageFile) {
            $data['image'] = $imageFile->store('products', config('filesystems.media_disk'));
        }

        $data = $this->normalizeVariantData($data);

        $product = Product::create($data);
        $this->embedProduct($product);

        return $product;
    }

    public function update(Product $product, array $data, $imageFile = null): Product
    {
        if ($imageFile) {
            if ($product->image) {
                Storage::disk(config('filesystems.media_disk'))->delete($product->image);
            }
            $data['image'] = $imageFile->store('products', config('filesystems.media_disk'));
        }

        $data = $this->normalizeVariantData($data);

        $product->update($data);
        $this->embedProduct($product);

        return $product;
    }

    /**
     * Normalize size/color input for storage. Products have a single
     * informational colour (stored as a one-element color_options array for
     * schema compatibility); stock is tracked per size only, so the legacy
     * per-colour matrix is always cleared.
     */
    private function normalizeVariantData(array $data): array
    {
        $data['sizes'] = $data['sizes'] ?? [];

        $color = isset($data['color']) && trim((string) $data['color']) !== ''
            ? trim((string) $data['color'])
            : null;
        unset($data['color']);
        $data['color_options'] = $color ? [$color] : null;
        $data['colors_stock']  = null;

        $data['sizes_stock'] = $this->buildSizesStockArray(
            $data['sizes_stock'] ?? null,
            $data['sizes']
        );

        if (empty($data['compare_at_price'])) {
            $data['compare_at_price'] = null;
        }

        return $data;
    }

    private function embedProduct(Product $product): void
    {
        try {
            $product->load('category');
            $vector = $this->embeddingService->embed($this->embeddingService->textForProduct($product));
            $product->update(['embedding' => $vector]);
        } catch (\Throwable $e) {
            Log::error('Product embedding failed', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
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
            Storage::disk(config('filesystems.media_disk'))->delete($product->image);
        }

        $product->delete();
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

}
