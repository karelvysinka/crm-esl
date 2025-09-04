<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppLink;

class AppLinkSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'ESL', 'url' => 'https://www.esl.cz', 'icon_url' => '/images/brands/bootstrap.svg', 'position' => 0],
            ['name' => 'E-shop', 'url' => 'https://eshop.esl.cz', 'icon_url' => '/images/brands/bootstrap.svg', 'position' => 1],
            ['name' => 'Indab', 'url' => 'https://indab.cz', 'icon_url' => '/images/brands/bootstrap.svg', 'position' => 2],
            ['name' => 'Invysys', 'url' => 'https://invysys.cz', 'icon_url' => '/images/brands/bootstrap.svg', 'position' => 3],
            ['name' => 'Kupr', 'url' => 'https://kupr.cz', 'icon_url' => '/images/brands/bootstrap.svg', 'position' => 4],
        ];
        foreach ($defaults as $d) {
            AppLink::firstOrCreate(
                ['url' => $d['url']],
                $d + ['is_active' => true]
            );
        }
    }
}
