<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

class ColumnActionGroup
{
    protected ?string $label = null;
    protected ?string $icon = 'Ellipsis';
    protected string $iconPosition = 'right';
    protected array $actions = [];
    protected array $props = [];

    public static function make(): self
    {
        return new self();
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

    public function icon(string $icon, string $position = 'right'): self
    {
        $this->icon = $icon;
        $this->iconPosition = $position;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getIconPosition(): ?string
    {
        return $this->iconPosition;
    }

    public function props(array $props): self
    {
        $this->props = $props;

        return $this;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function actions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->getLabel(),
            'icon' => $this->icon,
            'iconPosition' => $this->iconPosition,
            'props' => $this->props,
            'actions' => array_map(function (ColumnAction $action) {
                return $action->toArray();
            }, $this->actions),
        ];
    }

    public function toArrayWithModel($model): array
    {
        return [
            'label' => $this->getLabel(),
            'icon' => $this->icon,
            'iconPosition' => $this->iconPosition,
            'props' => $this->props,
            'actions' => array_map(function (ColumnAction $action) use ($model) {
                $actionArray = $action->toArray();
                if ($action->hasUrlCallback()) {
                    $actionArray['url'] = $action->executeUrlCallback($model);
                }
                return $actionArray;
            }, $this->actions),
        ];
    }
}
