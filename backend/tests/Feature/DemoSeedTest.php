<?php

namespace Tests\Feature;

use App\Models\Portal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_demo_portals_in_testing_environment(): void
    {
        Portal::factory()->count(2)->create();

        $response = $this->postJson('/api/portals/seed-demo');

        $response->assertOk();
        $response->assertJsonPath('portals_created', 5);
        $this->assertDatabaseCount('portals', 5);
        $this->assertDatabaseCount('portal_logs', 0);
    }

    public function test_is_forbidden_in_production_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->postJson('/api/portals/seed-demo');

        $response->assertStatus(403);

        app()->detectEnvironment(fn () => 'testing');
    }
}
