<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductsPermissionsSync extends Command
{
    protected $signature = 'products:permissions-sync';
    protected $description = 'Synchronize products.* permissions and assign to admin role';

    public function handle(): int
    {
        $perms = ['products.view','products.sync'];
        foreach ($perms as $p) {
            Permission::findOrCreate($p);
        }
        if ($admin = Role::where('name','admin')->first()) {
            $admin->givePermissionTo($perms);
            $this->info('Assigned products perms to admin');
        } else {
            $this->warn('Admin role not found – permissions created only');
        }
        return 0;
    }
}
