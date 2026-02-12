<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => $this->id,
            'user_id' => $this->user_id,
            'items'   => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
