<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    protected static function newFactory()
    {
        return TestModelFactory::new();
    }
}