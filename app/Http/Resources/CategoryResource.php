<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'products_count' => $this->resource->products_count ?? null,
            'products'    => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
