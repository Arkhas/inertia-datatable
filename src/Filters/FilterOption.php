<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Filters;

use Illuminate\Database\Eloquent\Builder;

class FilterOption
{
    protected string $value;
    protected ?string $label = null;
    protected ?string $icon = null;
    protected $queryCallback = null;

    public static function make(string $value): self
    {
        $instance = new self();
        $instance->value = $value;
        $instance->label = ucfirst($value);

        return $instance;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    protected ?string $iconPosition = 'left';

    public function icon(string $icon, string $position = 'left'): self
    {
        $this->icon = $icon;
        $this->iconPosition = $position;

        return $this;
    }

    public function getIconPosition(): ?string
    {
        return $this->iconPosition;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function query(callable $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }

    public function getQueryCallback(): ?callable
    {
        return $this->queryCallback;
    }

    public function applyQuery(Builder $query, $keyword): void
    {
        if ($this->queryCallback) {
            $query->orWhere(function ($subQuery) use ($keyword) {
                call_user_func($this->queryCallback, $subQuery, $keyword);
            });
        } else {
            $query->orWhere(function ($query) use ($keyword) {
                $query->where($this->value, 'like', "%{$keyword}%");
            });
        }
    }
}
