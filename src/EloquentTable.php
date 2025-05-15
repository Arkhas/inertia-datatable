<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable;

use Illuminate\Database\Eloquent\Builder;

class EloquentTable
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

    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function filters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function actions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }
}