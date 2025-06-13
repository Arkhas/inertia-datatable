<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

use Arkhas\InertiaDatatable\Traits\HasIcon;
use Illuminate\Database\Eloquent\Builder;

class Column
{
    use HasIcon;

    protected string  $name;
    protected ?string $label          = null;
    protected         $orderCallback  = null;
    protected         $filterCallback = null;
    protected         $htmlCallback   = null;
    protected bool    $sortable       = true;
    protected bool    $searchable     = true;
    protected bool    $toggable       = true;
    protected ?array  $relationPath   = null;
    protected ?string $width          = null;
    protected bool    $exportable     = true;
    protected         $exportCallback = null;

    public static function make(string $name): self
    {
        $column = new self();

        if (!str_contains($name, '.')) {
            $column->name = $name;
            return $column;
        }

        $parts = explode('.', $name);
        $column->name = array_pop($parts);
        $column->relationPath = $parts;

        return $column;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFullName(): string
    {
        if ($this->relationPath) {
            return implode('.', $this->relationPath) . '.' . $this->name;
        }

        return $this->name;
    }

    public function getRelationPath(): ?array
    {
        return $this->relationPath;
    }

    public function hasRelation(): bool
    {
        return $this->relationPath !== null;
    }

    public function order(callable $callback): self
    {
        $this->orderCallback = $callback;

        return $this;
    }

    public function filter(callable $callback): self
    {
        $this->filterCallback = $callback;

        return $this;
    }

    public function html(callable $callback): self
    {
        $this->htmlCallback = $callback;

        return $this;
    }

    public function applyOrder(Builder $query, string $order): void
    {
        if (!$this->sortable) {
            return;
        }

        if ($this->orderCallback) {
            call_user_func($this->orderCallback, $query, $order);
        } else {
            if ($this->hasRelation()) {
                 $query->orderByLeftPowerJoins($this->getFullName(), $order);
            } else {
                // For regular columns, use a simple orderBy
                $query->orderBy($this->name, $order);
            }
        }
    }

    public function applyFilter(Builder $query, $keywords): void
    {
        if (!$this->searchable) {
            return;
        }

        $keywords = is_array($keywords) ? $keywords : [$keywords];

        foreach ($keywords as $keyword) {
            if ($this->filterCallback) {
                call_user_func($this->filterCallback, $query, $keyword);
                continue;
            }

            if (!$this->hasRelation()) {
                $query->where(fn($q) => $q->orWhere($this->name, 'like', "%{$keyword}%"));
                continue;
            }

            // Handle relations
            $paths = $this->relationPath;
            $rel = array_shift($paths);
            $query->orWhereHas($rel, function ($query) use ($paths, $keyword) {
                $this->buildNestedWhereHas($query, $paths, $this->name, $keyword);
            });
        }
    }

    protected function buildNestedWhereHas(Builder $query, array $path, string $column, string $keyword): void
    {
        if (empty($path)) {
            $query->where($column, 'like', "%{$keyword}%");
            return;
        }

        $relation = array_shift($path);
        $query->whereHas($relation, function ($subq) use ($path, $column, $keyword) {
            $this->buildNestedWhereHas($subq, $path, $column, $keyword);
        });
    }

    public function renderHtml(object $model): ?string
    {
        if ($this->htmlCallback) {
            return call_user_func($this->htmlCallback, $model);
        }

        // No custom renderer, use default behavior
        if (!$this->hasRelation()) {
            return $model->{$this->name};
        }

        foreach ($this->relationPath as $rel) {
            if (!$model || !isset($model->{$rel})) {
                return null;
            }
            $model = $model->{$rel};
        }

        return $model ? $model->{$this->name} : null;
    }

    public function getOrderCallback(): ?callable
    {
        return $this->orderCallback;
    }

    public function getFilterCallback(): ?callable
    {
        return $this->filterCallback;
    }

    public function getHtmlCallback(): ?callable
    {
        return $this->htmlCallback;
    }


    public function label(?string $label = null): self
    {
        $this->label = $label ?? ucfirst(str_replace('_', ' ', $this->getName()));

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->getName()));
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function toggable(bool $toggable = true): self
    {
        $this->toggable = $toggable;

        return $this;
    }

    public function isToggable(): bool
    {
        return $this->toggable;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function width(?string $width = null): self
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function exportable(bool $exportable = true): self
    {
        $this->exportable = $exportable;

        return $this;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function exportAs(callable $callback): self
    {
        $this->exportCallback = $callback;

        return $this;
    }

    public function getExportValue(object $model): mixed
    {
        if ($this->exportCallback !== null) {
            return call_user_func($this->exportCallback, $model);
        }

        return $this->renderHtml($model);
    }

    public function toArray(): array
    {
        return [
            'name'       => $this->getName(),
            'label'      => $this->getLabel(),
            'hasIcon'    => $this->hasIcon(),
            'sortable'   => $this->isSortable(),
            'searchable' => $this->isSearchable(),
            'toggable'   => $this->isToggable(),
            'iconPosition' => $this->getIconPosition(),
            'width'      => $this->getWidth(),
        ];
    }
}
