<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->cart = Cart::create(['user_id' => $this->user->id]);
    }

    public function test_checkout_fails_when_product_out_of_stock()
    {
        $product = Product::factory()->outOfStock()->create();

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'quantity' => 1,
            'unit_price' => 99.99,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkout', [
                'payment_method' => 'credit_card',
                'address' => '123 Main St',
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_fails_with_insufficient_stock()
    {
        $product = Product::factory()->create([
            'stock' => 1,
            'price' => 99.99,
        ]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'quantity' => 5,
            'unit_price' => 99.99,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkout', [
                'payment_method' => 'credit_card',
                'address' => '123 Main St',
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_fails_with_invalid_coupon()
    {
        $product = Product::factory()->create([
            'stock' => 50,
            'price' => 100,
        ]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'quantity' => 2,
            'unit_price' => 100,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkout', [
                'payment_method' => 'credit_card',
                'address' => '123 Main St',
                'coupon_code' => 'INVALID-COUPON',
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_fails_with_empty_cart()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkout', [
                'payment_method' => 'credit_card',
                'address' => '123 Main St',
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_endpoint_accessible()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkout', [
                'payment_method' => 'credit_card',
                'address' => '123 Main St',
            ]);

        // Should fail due to empty cart or invalid data, but endpoint exists
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(422),
                $this->equalTo(201)
            )
        );
    }
}
