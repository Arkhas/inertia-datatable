<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

class CheckboxColumn extends Column
{
    protected $valueCallback = null;
    protected $checkedCallback = null;
    protected $disabledCallback = null;
    protected string $valueField = 'id';
    protected bool $toggable = false;
    protected ?string $width = '40px';
    protected bool $exportable = false;

    public static function make(string|callable $name = 'id'): self
    {
        $instance = new self();
        $instance->name = 'checks';

        // Set sortable, and searchable to false by default for checkbox columns
        $instance->sortable = false;
        $instance->searchable = false;

        // Set the value field (can be a string or a callback)
        if (is_callable($name)) {
            $instance->valueCallback = $name;
        } else {
            $instance->valueField = $name;
        }

        return $instance;
    }

    public function checked(callable $callback): self
    {
        $this->checkedCallback = $callback;

        return $this;
    }

    public function disabled(callable $callback): self
    {
        $this->disabledCallback = $callback;

        return $this;
    }

    public function getValue(object $model): mixed
    {
        if ($this->valueCallback !== null) {
            return call_user_func($this->valueCallback, $model);
        }

        return $model->{$this->valueField};
    }

    public function isChecked(object $model): bool
    {
        if ($this->checkedCallback === null) {
            return false;
        }

        return (bool) call_user_func($this->checkedCallback, $model);
    }

    public function isDisabled(object $model): bool
    {
        if ($this->disabledCallback === null) {
            return false;
        }

        return (bool) call_user_func($this->disabledCallback, $model);
    }

    public function renderHtml(object $model): ?string
    {
        // For checkbox columns, we don't need to render HTML
        // The checkbox will be rendered by the frontend
        return null;
    }

    public function getValueCallback(): ?callable
    {
        return $this->valueCallback;
    }

    public function getCheckedCallback(): ?callable
    {
        return $this->checkedCallback;
    }

    public function getDisabledCallback(): ?callable
    {
        return $this->disabledCallback;
    }

    public function toArray(): array
    {
        $columnData = [
            'name'         => $this->getName(),
            'label'        => $this->getLabel(),
            'hasIcon'      => $this->getIconCallback() !== null,
            'sortable'     => $this->isSortable(),
            'searchable'   => $this->isSearchable(),
            'toggable'     => $this->isToggable(),
            'iconPosition' => $this->getIconPosition() ?? 'left',
            'type'         => 'checkbox'
        ];

        return $columnData;
    }
}
