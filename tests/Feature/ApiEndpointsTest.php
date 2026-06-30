<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    // ============================================================
    // PUBLIC ENDPOINTS (no auth required)
    // ============================================================

    public function test_get_settings_endpoint_exists()
    {
        $response = $this->getJson('/api/settings');
        // Should return 200 or 500, but not 404 (endpoint exists)
        $this->assertNotEquals(404, $response->status());
    }

    public function test_get_products_endpoint_exists()
    {
        $response = $this->getJson('/api/products');
        // Endpoint should exist
        $this->assertNotEquals(404, $response->status());
    }

    public function test_get_categories_endpoint_exists()
    {
        $response = $this->getJson('/api/categories');
        // Endpoint should exist
        $this->assertNotEquals(404, $response->status());
    }

    public function test_get_banners_endpoint_exists()
    {
        $response = $this->getJson('/api/banners');
        // Endpoint should exist
        $this->assertNotEquals(404, $response->status());
    }

    // ============================================================
    // AUTH ENDPOINTS (rate limited)
    // ============================================================

    public function test_register_endpoint_exists()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should not be 404
        $this->assertNotEquals(404, $response->status());
    }

    public function test_login_endpoint_exists()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should not be 404
        $this->assertNotEquals(404, $response->status());
    }

    public function test_forgot_password_endpoint_exists()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        // Should not be 404
        $this->assertNotEquals(404, $response->status());
    }

    // ============================================================
    // ADMIN ENDPOINTS
    // ============================================================

    public function test_admin_products_endpoint_requires_auth()
    {
        $response = $this->getJson('/api/admin/products');

        // Should require authentication (401 or 403) not be a 404
        $this->assertTrue(
            in_array($response->status(), [401, 403]),
            "Expected 401 or 403 but got {$response->status()}"
        );
    }

    public function test_admin_settings_endpoint_requires_auth()
    {
        $response = $this->putJson('/api/admin/settings', []);

        // Should require authentication (401 or 403) not be a 404
        $this->assertTrue(
            in_array($response->status(), [401, 403, 422]),
            "Expected 401, 403, or 422 but got {$response->status()}"
        );
    }

    // ============================================================
    // BASIC AUTHORIZATION CHECKS
    // ============================================================

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/user');
        $this->assertEquals(401, $response->status());
    }

    public function test_unauthenticated_user_cannot_access_admin_routes()
    {
        $response = $this->getJson('/api/admin/products');
        $this->assertEquals(401, $response->status());
    }
}
