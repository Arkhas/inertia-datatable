<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable;

use Illuminate\Database\Eloquent\Builder;

class EloquentTable extends Table
{
    protected array $columns = [];
    protected array $filters = [];
    protected array $actions = [];
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public static function make(Builder $query): self
    {
        return new self($query);
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }
}