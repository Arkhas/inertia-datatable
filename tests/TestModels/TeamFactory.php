<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'department' => $this->faker->randomElement(['Engineering', 'Marketing', 'Sales', 'Support']),
        ];
    }
}