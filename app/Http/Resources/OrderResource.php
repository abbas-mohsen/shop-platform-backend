<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'total'          => (float) $this->total,
            'status'         => $this->status,
            'payment_method' => $this->payment_method,
            'address'        => $this->address,
            'user'           => new UserResource($this->whenLoaded('user')),
            'items'          => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at'     => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at'     => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
