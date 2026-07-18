<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockRestoreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        // Plain arrays — the model's 'array' cast JSON-encodes them once.
        // (json_encode here would double-encode through the cast.)
        $this->product = Product::factory()->create([
            'stock'       => 10,
            'sizes'       => ['M'],
            'sizes_stock' => ['M' => 5],
        ]);

        $customer = User::factory()->create();

        $this->order = Order::create([
            'user_id'        => $customer->id,
            'payment_method' => 'cash',
            'status'         => 'pending',
            'total'          => 40,
            'address'        => 'Test Street 1',
        ]);

        OrderItem::create([
            'order_id'   => $this->order->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
            'unit_price' => 20,
            'line_total' => 40,
            'size'       => 'M',
        ]);
    }

    private function setStatus(string $status)
    {
        return $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/orders/{$this->order->id}", ['status' => $status]);
    }

    public function test_rejecting_an_order_restores_stock()
    {
        $this->setStatus('rejected')->assertOk();

        $this->product->refresh();
        $this->assertEquals(7, $this->product->sizes_stock['M']);
        $this->assertEquals(12, $this->product->stock);
    }

    public function test_reactivating_a_rejected_order_deducts_stock_again()
    {
        $this->setStatus('rejected')->assertOk();
        $this->setStatus('approved')->assertOk();

        $this->product->refresh();
        $this->assertEquals(5, $this->product->sizes_stock['M']);
        $this->assertEquals(10, $this->product->stock);
    }

    public function test_rejected_then_cancelled_does_not_restore_twice()
    {
        $this->setStatus('rejected')->assertOk();
        $this->setStatus('cancelled')->assertOk();

        $this->product->refresh();
        $this->assertEquals(7, $this->product->sizes_stock['M']);
        $this->assertEquals(12, $this->product->stock);
    }

    public function test_approving_then_delivering_never_touches_stock()
    {
        $this->setStatus('approved')->assertOk();
        $this->setStatus('delivered')->assertOk();

        $this->product->refresh();
        $this->assertEquals(5, $this->product->sizes_stock['M']);
        $this->assertEquals(10, $this->product->stock);
    }
}
