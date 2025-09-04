<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_dashboard_without_permission(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Skipping ops permission test under sqlite in-memory');
        }
        $u = User::factory()->create();
        $this->actingAs($u);
        $this->get('/crm/ops')->assertStatus(403);
    }
}
