<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Filters;

use Illuminate\Database\Eloquent\Builder;

class Filter
{
    protected string $name;
    protected string $label;
    protected array  $options       = [];
    protected array  $filterOptions = [];
    protected array  $icons         = [];
    protected array  $iconPositions = [];
    protected bool   $multiple      = false;
    protected        $queryCallback = null;

    public static function make(string $name): self
    {
        $instance        = new self();
        $instance->name  = $name;
        $instance->label = ucfirst($name);

        return $instance;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function options(array $options): self
    {
        // Check if the options array contains FilterOption objects
        $containsFilterOptions = false;
        foreach ($options as $option) {
            if ($option instanceof FilterOption) {
                $containsFilterOptions = true;
                break;
            }
        }

        if ($containsFilterOptions) {
            $this->filterOptions = $options;

            // Also update the options array for backward compatibility
            $this->options       = [];
            $this->icons         = [];
            $this->iconPositions = [];

            foreach ($options as $option) {
                $this->options[$option->getValue()] = $option->getLabel();
                if ($option->getIcon()) {
                    $this->icons[$option->getValue()]         = $option->getIcon();
                    $this->iconPositions[$option->getValue()] = $option->getIconPosition();
                }
            }
        } else {
            $this->options = $options;
        }

        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function query(callable $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }

    public function applyFilter(Builder $query, $values): void
    {
        if (!is_array($values) && !empty($values)) {
            // Handle comma-separated values
            if (str_contains($values, ',')) {
                $values = explode(',', $values);
            } else {
                $values = [$values];
            }
        }

        if (!empty($values)) {
            // Apply the filter's query callback if it exists
            if ($this->queryCallback) {
                call_user_func($this->queryCallback, $query, $values);
            }

            // If we have FilterOption objects with query callbacks, use them
            if (!empty($this->filterOptions)) {
                $query->where(function ($subQuery) use ($values) {
                    foreach ($values as $value) {
                        // Find the corresponding FilterOption
                        foreach ($this->filterOptions as $option) {
                            if ($option->getValue() === $value) {
                                // Apply the FilterOption's query callback
                                $option->applyQuery($subQuery, $value);
                            }
                        }
                    }
                });
            } elseif (!$this->queryCallback) {
                // Fall back to the default behavior if no query callbacks are defined
                $query->whereIn($this->name, $values);
            }
        }
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getFilterOptions(): array
    {
        return $this->filterOptions;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function getQueryCallback(): ?callable
    {
        return $this->queryCallback;
    }

    public function icons(array $icons): self
    {
        $this->icons = $icons;

        return $this;
    }

    public function getIcons(): array
    {
        return $this->icons;
    }

    public function iconPositions(array $iconPositions): self
    {
        $this->iconPositions = $iconPositions;

        return $this;
    }

    public function getIconPositions(): array
    {
        return $this->iconPositions;
    }


    public function toArray(): array
    {
        $data = [
            'name'          => $this->getName(),
            'label'         => $this->getLabel(),
            'options'       => $this->getOptions(),
            'icons'         => $this->getIcons(),
            'iconPositions' => $this->getIconPositions(),
            'multiple'      => $this->isMultiple()
        ];

        if (!empty($this->filterOptions)) {
            $data['filterOptions'] = array_map(function ($option) {
                return $option->toArray();
            }, $this->filterOptions);
        }

        return $data;
    }
}
