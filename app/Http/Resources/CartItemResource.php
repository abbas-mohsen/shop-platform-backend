<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'cart_id'    => $this->cart_id,
            'product_id' => $this->product_id,
            'size'       => $this->size,
            'quantity'   => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'product'    => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
