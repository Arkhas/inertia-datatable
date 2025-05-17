<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestModel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'user_id'];

    public function user(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): TestModelFactory
    {
        return TestModelFactory::new();
    }
}
