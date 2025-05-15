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

    public static function make(string $name): self
    {
        $instance       = new self();
        $instance->name = $name;

        return $instance;
    }

    public function getName(): string
    {
        return $this->name;
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
            $query->orderBy($this->name, $order);
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
                    $query->where(fn($query) => $query->orWhere($this->name, 'like', "%$keyword%"));
                }
            }
        }
    }

    public function renderHtml(object $model): ?string
    {
        if ($this->htmlCallback === null) {
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

    public function label(string $label): self
    {
        $this->label = $label;

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

    public function issearchable(): bool
    {
        return $this->searchable;
    }

    public function toArray(): array
    {
        $columnData = [
            'name'       => $this->getName(),
            'label'      => $this->getLabel() ?? ucfirst(str_replace('_', ' ', $this->getName())),
            'hasIcon'    => $this->getIconCallback() !== null,
            'sortable'   => $this->isSortable(),
            'searchable' => $this->issearchable(),
            'toggable'   => $this->isToggable()
        ];

        // Add iconPosition if available
        if ($this->getIconPosition()) {
            $columnData['iconPosition'] = $this->getIconPosition();
        }

        return $columnData;
    }
}
