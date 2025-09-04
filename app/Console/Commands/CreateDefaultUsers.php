<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDefaultUsers extends Command
{
    protected $signature = 'app:create-default-users 
        {--admin-email=admin@crm.local} 
        {--admin-pass=ChangeMe123!} 
        {--user-email=user@crm.local} 
        {--user-pass=ChangeMe123!}';

    protected $description = 'Create default admin and user accounts if they do not exist';

    public function handle(): int
    {
        $admin = User::updateOrCreate(
            ['email' => $this->option('admin-email')],
            [
                'name' => 'Administrator',
                // assign plain password; Laravel 'hashed' cast will hash it once
                'password' => $this->option('admin-pass'),
                'is_admin' => true,
            ]
        );
        $this->info('Admin: '.$admin->email);

        $user = User::updateOrCreate(
            ['email' => $this->option('user-email')],
            [
                'name' => 'Uživatel',
                'password' => $this->option('user-pass'),
                'is_admin' => false,
            ]
        );
        $this->info('User: '.$user->email);

        $this->line('Done.');
        return self::SUCCESS;
    }
}
