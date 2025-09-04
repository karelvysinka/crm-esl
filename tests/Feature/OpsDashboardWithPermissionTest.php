<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class OpsDashboardWithPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_dashboard_with_permission(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Skipping ops permission positive test under sqlite');
        }
        if (!class_exists(\Spatie\Permission\Models\Permission::class)) {
            $this->markTestSkipped('Permission package not installed');
        }
        Artisan::call('migrate', ['--force'=>true]);
        \Spatie\Permission\Models\Permission::findOrCreate('ops.view');
        $u = User::factory()->create();
        $u->givePermissionTo('ops.view');
        $this->actingAs($u);
        $this->get('/crm/ops')->assertStatus(200);
    }
}
