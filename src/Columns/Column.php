<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

use Illuminate\Database\Eloquent\Builder;

class Column
{
    protected string  $name;
    protected ?string $label          = null;
    protected         $orderCallback  = null;
    protected         $filterCallback = null;
    protected         $htmlCallback   = null;
    protected         $iconCallback   = null;
    protected ?string $iconPosition   = 'left';
    protected bool    $sortable       = true;
    protected bool    $searchable     = true;
    protected bool    $toggable       = true;
    protected ?array  $relationPath   = null;
    protected ?string $width          = null;

    public static function make(string $name): self
    {
        $instance = new self();

        // Check if the column name contains relationships (dots)
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $instance->name = end($parts); // The actual column name is the last part

            // The relation path is everything except the last part
            $instance->relationPath = array_slice($parts, 0, -1);
        } else {
            $instance->name = $name;
        }

        return $instance;
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
                 $query->orderByPowerJoins($this->getFullName(), $order);
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

        if (!is_array($keywords)) {
            $keywords = [$keywords];
        }

        if ($keywords) {
            foreach ($keywords as $keyword) {
                if ($this->filterCallback) {
                    call_user_func($this->filterCallback, $query, $keyword);
                } else {
                    if ($this->hasRelation()) {
                        // For relationship columns, use whereHas to search in related models
                        $relationPath = $this->relationPath;
                        $columnName = $this->name;

                        // Build nested whereHas for each level of the relationship
                        $currentRelation = array_shift($relationPath);
                        $query->orWhereHas($currentRelation, function ($subQuery) use ($relationPath, $columnName, $keyword) {
                            $this->buildNestedWhereHas($subQuery, $relationPath, $columnName, $keyword);
                        });
                    } else {
                        // For regular columns, use a simple where clause
                        $query->where(fn($query) => $query->orWhere($this->name, 'like', "%$keyword%"));
                    }
                }
            }
        }
    }

    /**
     * Recursively build nested whereHas queries for deep relationships
     */
    protected function buildNestedWhereHas(Builder $query, array $relationPath, string $columnName, string $keyword): void
    {
        if (empty($relationPath)) {
            // We've reached the final relation, apply the where clause on the column
            $query->where($columnName, 'like', "%$keyword%");
        } else {
            // We still have relations to traverse, continue with whereHas
            $currentRelation = array_shift($relationPath);
            $query->whereHas($currentRelation, function ($subQuery) use ($relationPath, $columnName, $keyword) {
                $this->buildNestedWhereHas($subQuery, $relationPath, $columnName, $keyword);
            });
        }
    }

    public function renderHtml(object $model): ?string
    {
        if ($this->htmlCallback === null) {
            if ($this->hasRelation()) {
                // Traverse the relationship path to get the final value
                $value = $model;
                foreach ($this->relationPath as $relation) {
                    if (!$value || !isset($value->{$relation})) {
                        return null; // Return null if any part of the path is null
                    }
                    $value = $value->{$relation};
                }

                // Get the final property value if the relationship object exists
                return $value ? $value->{$this->name} : null;
            }

            return $model->{$this->name};
        }

        return call_user_func($this->htmlCallback, $model);
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

    public function icon(callable $callback, string $position = 'left'): self
    {
        $this->iconCallback = $callback;
        $this->iconPosition = $position;

        return $this;
    }

    public function getIconCallback(): ?callable
    {
        return $this->iconCallback;
    }

    public function getIconPosition(): ?string
    {
        return $this->iconPosition;
    }

    public function renderIcon(object $model): ?string
    {
        if ($this->iconCallback === null) {
            return null;
        }

        return call_user_func($this->iconCallback, $model);
    }

    public function label(?string $label = null): self
    {
        $this->label = $label ?? ucfirst(str_replace('_', ' ', $this->getName()));

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
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

    public function toArray(): array
    {
        $columnData = [
            'name'       => $this->getName(),
            'label'      => $this->getLabel() ?? ucfirst(str_replace('_', ' ', $this->getName())),
            'hasIcon'    => $this->getIconCallback() !== null,
            'sortable'   => $this->isSortable(),
            'searchable' => $this->isSearchable(),
            'toggable'   => $this->isToggable()
        ];

        // Add iconPosition if available
        if ($this->getIconPosition()) {
            $columnData['iconPosition'] = $this->getIconPosition();
        }

        // Add width if available
        if ($this->getWidth()) {
            $columnData['width'] = $this->getWidth();
        }

        return $columnData;
    }
}
