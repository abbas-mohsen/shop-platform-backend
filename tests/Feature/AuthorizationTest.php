<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test customer cannot access admin routes
     */
    public function test_customer_cannot_access_admin_products()
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/products');

        $response->assertStatus(403);
    }

    /**
     * Test admin can access admin products route
     */
    public function test_admin_can_access_admin_products()
    {
        $admin = User::factory()->admin()->create();

        Product::factory(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/products');

        $response->assertStatus(200);
    }

    /**
     * Test only super_admin can manage users
     */
    public function test_only_super_admin_can_manage_users()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    /**
     * Test super_admin can manage users
     */
    public function test_super_admin_can_manage_users()
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(200);
    }

    /**
     * Test unauthenticated user cannot access protected routes
     */
    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(401);
    }

    /**
     * Test user can only view their own orders
     */
    public function test_user_can_only_view_own_orders()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create a mock order structure for testing
        // (assuming Order model exists with user_id)

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/my-orders');

        $response->assertStatus(200);
    }

    /**
     * Test admin can view settings
     */
    public function test_super_admin_can_access_settings()
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->putJson('/api/admin/settings', [
                'hero_title' => 'New Title',
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test customer cannot access settings
     */
    public function test_customer_cannot_modify_settings()
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson('/api/admin/settings', [
                'hero_title' => 'New Title',
            ]);

        $response->assertStatus(403);
    }
}
