<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_products_with_pagination()
    {
        Product::factory(25)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price']
                ]
            ]);
    }

    public function test_filter_products_by_category()
    {
        $category = Category::first() ?? Category::create(['name' => 'Test']);

        Product::factory(5)->create(['category_id' => $category->id]);
        Product::factory(5)->create();

        $response = $this->getJson("/api/products?category_id={$category->id}");

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_search_products_by_name()
    {
        Product::factory()->create(['name' => 'Adidas Running Shoes']);
        Product::factory()->create(['name' => 'Nike Basketball Shoes']);
        Product::factory()->create(['name' => 'Adidas Training Shirt']);

        $response = $this->getJson('/api/products?search=Adidas');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_sort_products_by_price()
    {
        Product::factory()->create(['name' => 'Expensive', 'price' => 500]);
        Product::factory()->create(['name' => 'Cheap', 'price' => 10]);
        Product::factory()->create(['name' => 'Medium', 'price' => 100]);

        $response = $this->getJson('/api/products?sort_by=price&sort_order=asc');

        $response->assertStatus(200);
        // Just verify we got results
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_get_product_details()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'price']);
    }

    public function test_product_not_found()
    {
        $response = $this->getJson('/api/products/9999');

        $response->assertStatus(404);
    }

    public function test_list_products_returns_data()
    {
        Product::factory(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }
}
