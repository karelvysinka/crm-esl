<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OrdersPermissionsSync extends Command
{
    protected $signature = 'orders:permissions-sync';
    protected $description = 'Synchronize orders.* permissions and assign to admin role';

    public function handle(): int
    {
        $perms = ['orders.view'];
        foreach ($perms as $p) { Permission::findOrCreate($p); }
        if ($admin = Role::where('name','admin')->first()) {
            $admin->givePermissionTo($perms);
            $this->info('Assigned orders perms to admin');
        } else {
            $this->warn('Admin role not found – permissions created only');
        }
        return 0;
    }
}
