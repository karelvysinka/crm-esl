<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;

class CrmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user if not exists
        $user = User::firstOrCreate([
            'email' => 'admin@crm.esl.cz'
        ], [
            'name' => 'CRM Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Create test companies
        $companies = [
            [
                'name' => 'Tech Solutions s.r.o.',
                'industry' => 'Information Technology',
                'size' => 'medium',
                'status' => 'active',
                'website' => 'https://techsolutions.cz',
                'phone' => '+420 123 456 789',
                'email' => 'info@techsolutions.cz',
                'city' => 'Prague',
                'country' => 'Czech Republic',
                'annual_revenue' => 5000000,
                'employee_count' => 50,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Marketing Pro a.s.',
                'industry' => 'Marketing',
                'size' => 'small',
                'status' => 'prospect',
                'website' => 'https://marketingpro.cz',
                'phone' => '+420 987 654 321',
                'email' => 'contact@marketingpro.cz',
                'city' => 'Brno',
                'country' => 'Czech Republic',
                'annual_revenue' => 2000000,
                'employee_count' => 25,
                'created_by' => $user->id,
            ],
        ];

        foreach ($companies as $companyData) {
            Company::create($companyData);
        }

        $this->command->info('CRM test data created successfully!');
    }
}
