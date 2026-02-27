<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['nullable', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sizes'            => ['nullable', 'array'],
            'sizes.*'          => ['string', 'max:10'],
            'sizes_stock'      => ['nullable'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'color_options'    => ['nullable', 'string'],
        ];
    }
}
