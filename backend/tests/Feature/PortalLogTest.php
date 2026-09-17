<?php

namespace Tests\Feature;

use App\Models\Portal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_list_when_no_logs_exist(): void
    {
        $response = $this->getJson('/api/logs');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    public function test_logs_are_sorted_by_timestamp_descending(): void
    {
        $portal = Portal::factory()->create(['creatures_count' => 0]);

        $this->postJson("/api/portals/{$portal->id}/action", ['action' => 'mark_under_review'])->assertOk();
        sleep(1); // timestamp-колонка имеет точность до секунды — разносим действия во времени для детерминированной сортировки
        $this->postJson("/api/portals/{$portal->id}/action", ['action' => 'close'])->assertOk();

        $response = $this->getJson('/api/logs');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.action_type', 'close');
        $response->assertJsonPath('data.1.action_type', 'mark_under_review');
    }

    public function test_filters_logs_by_portal_id(): void
    {
        $portalA = Portal::factory()->create(['creatures_count' => 0]);
        $portalB = Portal::factory()->create(['creatures_count' => 0]);

        $this->postJson("/api/portals/{$portalA->id}/action", ['action' => 'mark_under_review'])->assertOk();
        $this->postJson("/api/portals/{$portalB->id}/action", ['action' => 'mark_under_review'])->assertOk();

        $response = $this->getJson("/api/logs?portal_id={$portalA->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.portal_id', $portalA->id);
    }
}
