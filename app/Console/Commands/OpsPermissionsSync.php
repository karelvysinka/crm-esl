<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OpsPermissionsSync extends Command
{
    protected $signature = 'ops:permissions-sync {--force : Create even if package absent}';
    protected $description = 'Synchronize ops.* permissions (ops.view, ops.execute, ops.release) and assign to admin role if exists';

    public function handle(): int
    {
        if (!class_exists(\Spatie\Permission\Models\Permission::class)) {
            if ($this->option('force')) {
                $this->warn('spatie/laravel-permission not installed, force creating placeholders skipped.');
                return self::SUCCESS;
            }
            $this->error('Permission package not installed.');
            return self::FAILURE;
        }
        $perms = ['ops.view','ops.execute','ops.release'];
        foreach ($perms as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $role = \Spatie\Permission\Models\Role::where('name','admin')->first();
        if ($role) {
            $role->givePermissionTo($perms);
            $this->info('Assigned permissions to admin role.');
        }
        $this->info('Ops permissions synced.');
        return self::SUCCESS;
    }
}
