<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition():array
    {
        return [
            'name' => $this->faker->name,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'user_id' => User::factory(),
        ];
    }
}
