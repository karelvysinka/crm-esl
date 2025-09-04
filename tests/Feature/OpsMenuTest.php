<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class OpsMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_hidden_without_permission(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Skipping ops menu test under sqlite');
        }
        $u = User::factory()->create();
        $this->actingAs($u);
        $this->get('/crm')->assertDontSee('Ops (Git & Zálohy)');
    }

    public function test_menu_visible_with_permission(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Skipping ops menu test under sqlite');
        }
        if (!class_exists(\Spatie\Permission\Models\Permission::class)) {
            $this->markTestSkipped('Permission package not installed');
        }
        Artisan::call('migrate', ['--force'=>true]);
        \Spatie\Permission\Models\Permission::findOrCreate('ops.view');
        $u = User::factory()->create();
        $u->givePermissionTo('ops.view');
        $this->actingAs($u);
        $this->get('/crm')->assertSee('Ops (Git & Zálohy)');
    }
}
