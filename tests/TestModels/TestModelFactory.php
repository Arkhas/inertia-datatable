<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}