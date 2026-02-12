<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'product_id' => $this->product_id,
            'rating'     => (int) $this->rating,
            'comment'    => $this->comment,
            'user'       => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
        ];
    }
}
