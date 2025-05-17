<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'department'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
