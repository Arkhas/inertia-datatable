<?php

namespace Tests\TestModels;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait WithTestModels
{
    protected function setUpTestModels(): void
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->timestamps();
        });

        TestModel::factory()->create(['name' => 'Alice', 'status' => 'active']);
        TestModel::factory()->create(['name' => 'Bob', 'status' => 'inactive']);
        TestModel::factory()->create(['name' => 'Charlie', 'status' => 'active']);
    }

    protected function tearDownTestModels(): void
    {
        Schema::dropIfExists('test_models');
    }
}