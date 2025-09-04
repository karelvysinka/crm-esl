<?php

namespace Tests\Feature;

use App\Models\OpsActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsMetricsContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_contains_freshness_and_counters(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Skipping metrics content test under sqlite');
        }
        // Seed a few activities
        OpsActivity::factory()->create(['type'=>'db_backup','status'=>'success']);
        OpsActivity::factory()->create(['type'=>'storage_snapshot','status'=>'failed']);
        OpsActivity::factory()->create(['type'=>'verify_restore','status'=>'success']);
        $u = User::factory()->create();
        $u->givePermissionTo('ops.view');
        $this->actingAs($u);
        $resp = $this->get('/crm/ops/metrics');
        $resp->assertStatus(200);
        $body = $resp->getContent();
        $this->assertStringContainsString('ops_activity_total', $body);
        $this->assertStringContainsString('ops_activity_24h_total', $body);
        $this->assertStringContainsString('ops_db_dump_age_minutes', $body);
        $this->assertStringContainsString('ops_snapshot_age_minutes', $body);
        $this->assertStringContainsString('ops_verify_age_hours', $body);
    }
}
