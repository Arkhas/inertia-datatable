<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Services;

use Illuminate\Database\Eloquent\Builder;

class DatatableService
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function sort(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function search(string $keyword): self
    {
        $this->query->where(function ($query) use ($keyword) {
            foreach ($this->query->getModel()->getFillable() as $column) {
                $query->orWhere($column, 'like', "%$keyword%");
            }
        });
        return $this;
    }

    public function paginate(int $perPage, int $page): self
    {
        $this->query->skip(($page - 1) * $perPage)->take($perPage);
        return $this;
    }

    public function filter(string $column, string $value): self
    {
        $this->query->where($column, $value);
        return $this;
    }
}
