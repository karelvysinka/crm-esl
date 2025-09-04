<?php

namespace Database\Factories;

use App\Models\OpsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpsActivityFactory extends Factory
{
    protected $model = OpsActivity::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['db_backup','storage_snapshot','verify_restore','report']),
            'status' => $this->faker->randomElement(['queued','running','success','failed']),
            'user_id' => User::factory(),
            'started_at' => now()->subMinutes(rand(1,60)),
            'finished_at' => now(),
            'duration_ms' => rand(100, 5000),
            'meta' => null,
            'log_excerpt' => $this->faker->sentence(4)
        ];
    }
}
