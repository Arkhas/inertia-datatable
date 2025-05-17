<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email'];

    public function tasks(): hasMany
    {
        return $this->hasMany(TestModel::class, 'user_id');
    }

    public function team(): belongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
