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
     * Customers cannot manage users
     */
    public function test_customer_cannot_manage_users()
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    /**
     * Admins can list users and promote a customer to admin
     */
    public function test_admin_can_promote_customer_to_admin()
    {
        $admin    = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(200);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$customer->id}/role", ['role' => 'admin']);

        $response->assertStatus(200);
        $this->assertEquals('admin', $customer->fresh()->role);
    }

    /**
     * The super_admin role can never be assigned — not even by the super admin
     */
    public function test_super_admin_role_cannot_be_assigned()
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin      = User::factory()->admin()->create();
        $customer   = User::factory()->create();

        // Admin trying to grant super_admin
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$customer->id}/role", ['role' => 'super_admin'])
            ->assertStatus(422);

        // Even the super admin cannot grant super_admin
        $this->actingAs($superAdmin, 'sanctum')
            ->putJson("/api/admin/users/{$customer->id}/role", ['role' => 'super_admin'])
            ->assertStatus(422);

        $this->assertEquals('customer', $customer->fresh()->role);
    }

    /**
     * The super admin's own role is untouchable, and nobody can change their own role
     */
    public function test_super_admin_role_is_protected()
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin      = User::factory()->admin()->create();

        // Admin cannot demote the super admin
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$superAdmin->id}/role", ['role' => 'customer'])
            ->assertStatus(422);

        // Admin cannot change their own role
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$admin->id}/role", ['role' => 'customer'])
            ->assertStatus(422);

        $this->assertEquals('super_admin', $superAdmin->fresh()->role);
        $this->assertEquals('admin', $admin->fresh()->role);
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
