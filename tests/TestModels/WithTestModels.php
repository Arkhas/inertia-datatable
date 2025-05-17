<?php

namespace Tests\TestModels;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait WithTestModels
{
    protected function setUpTestModels(): void
    {
        // Create teams table
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('department');
            $table->timestamps();
        });

        // Create users table with foreign key to teams
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });

        // Create test_models table with foreign key to users
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Create teams
        $engineeringTeam = Team::factory()->create(['name' => 'Engineering', 'department' => 'Engineering']);
        $marketingTeam = Team::factory()->create(['name' => 'Marketing', 'department' => 'Marketing']);

        // Create users with specific teams
        $john = User::factory()->create([
            'name' => 'John Doe', 
            'email' => 'john@example.com',
            'team_id' => $engineeringTeam->id
        ]);

        $jane = User::factory()->create([
            'name' => 'Jane Smith', 
            'email' => 'jane@example.com',
            'team_id' => $marketingTeam->id
        ]);

        $bob = User::factory()->create([
            'name' => 'Bob Johnson', 
            'email' => 'bob@example.com',
            'team_id' => $engineeringTeam->id
        ]);

        // Create test models with specific users
        TestModel::factory()->create([
            'name' => 'Alice', 
            'status' => 'active',
            'user_id' => $john->id
        ]);

        TestModel::factory()->create([
            'name' => 'Bob', 
            'status' => 'inactive',
            'user_id' => $jane->id
        ]);

        TestModel::factory()->create([
            'name' => 'Charlie', 
            'status' => 'active',
            'user_id' => $bob->id
        ]);
    }

    protected function tearDownTestModels(): void
    {
        // Drop tables in reverse order to avoid foreign key constraint issues
        Schema::dropIfExists('test_models');
        Schema::dropIfExists('users');
        Schema::dropIfExists('teams');
    }
}
