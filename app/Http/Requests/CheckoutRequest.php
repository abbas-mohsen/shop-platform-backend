<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address'            => ['required', 'string', 'max:500'],
            'payment_method'     => ['required', 'in:cod,card'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.size'       => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Your cart is empty.',
            'items.*.product_id.exists'   => 'One of the products in your cart no longer exists.',
            'items.*.quantity.min'         => 'Quantity must be at least 1.',
        ];
    }
}
