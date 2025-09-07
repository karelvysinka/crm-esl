<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base role
        $admin = Role::firstOrCreate(['name'=>'admin']);
        $ordersView = Permission::firstOrCreate(['name'=>'orders.view']);
        $admin->givePermissionTo($ordersView);
        // Grant all existing users temporarily (so they see Orders) – can refine later
        User::query()->chunkById(100, function($chunk) use ($ordersView){
            foreach ($chunk as $u) { $u->givePermissionTo($ordersView); }
        });
        $this->command->info('Admin role + orders.view permission synchronized.');
    }
}
