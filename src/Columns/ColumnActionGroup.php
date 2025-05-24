<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

use Arkhas\InertiaDatatable\Traits\HasIcon;

class ColumnActionGroup
{
    use HasIcon;

    protected ?string $label = null;
    protected array $actions = [];
    protected array $props = [];

    public static function make(): self
    {
        $instance = new self();
        $instance->icon = 'Ellipsis';
        $instance->iconPosition = 'right';

        return $instance;
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

    public function toArray($model = null): array
    {
        return [
            'label' => $this->getLabel(),
            'icon' => $this->renderIcon($model),
            'iconPosition' => $this->getIconPosition(),
            'props' => $this->props,
            'actions' => array_map(function (ColumnAction $action) use ($model) {
                $actionArray = $action->toArray($model);
                if ($action->hasUrlCallback()) {
                    $actionArray['url'] = $action->executeUrlCallback($model);
                }
                return $actionArray;
            }, $this->actions),
        ];
    }
}
