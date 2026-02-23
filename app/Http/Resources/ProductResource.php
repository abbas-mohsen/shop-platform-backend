<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'category_id'        => $this->category_id,
            'description'        => $this->description,
            'price'              => (float) $this->price,
            'stock'              => $this->stock,
            'image'              => $this->image,
            'sizes'              => $this->sizes,
            'sizes_stock'        => $this->sizes_stock,
            'available_sizes'    => $this->available_sizes,
            'category'           => new CategoryResource($this->whenLoaded('category')),
            'reviews_count'      => $this->when(isset($this->reviews_count), $this->reviews_count),
            'reviews_avg_rating' => $this->when(
                isset($this->reviews_avg_rating),
                fn () => $this->reviews_avg_rating ? round((float) $this->reviews_avg_rating, 1) : null
            ),
            'reviews'            => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at'         => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at'         => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
