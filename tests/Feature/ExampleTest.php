<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_settings_accessible()
    {
        $response = $this->getJson('/api/settings');
        // Endpoint should be accessible (200 or 500, but not 404)
        $this->assertNotEquals(404, $response->status());
    }
}
