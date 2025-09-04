<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_denies_metrics_without_permission(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Skipping ops metrics test under sqlite');
        }
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/crm/ops/metrics')->assertStatus(403);
    }
}
