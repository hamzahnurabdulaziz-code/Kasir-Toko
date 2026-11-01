<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Store::create([
            'name' => 'Toko Kasir V3',
            'receipt_prefix' => 'INV-',
            'qris_merchant_name' => 'TOKO KASIR V3',
            'qris_merchant_id' => 'MID-001',
        ]);
    }

    /**
     * PRD V3 Section 10: / dan /login langsung dialihkan ke /dashboard tanpa autentikasi.
     */
    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_login_redirects_to_dashboard(): void
    {
        $response = $this->get('/login');
        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}
