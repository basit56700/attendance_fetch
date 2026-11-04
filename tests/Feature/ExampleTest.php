<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('id="login-brand-logo"', false);
    }

    public function test_authenticated_dashboard_renders_sidebar_brand_logo(): void
    {
        $user = User::first();
        if (! $user) {
            $user = User::factory()->create();
        }

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="sidebar-brand-logo"', false);
        $response->assertSee('images/gcs-logo.png', false);
        $response->assertDontSee('Crisp White Logo Plaque', false);
    }
}
