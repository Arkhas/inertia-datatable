<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Actions;

class TableAction
{
    protected string $name;
    protected ?string $label = null;
    protected ?string $styles = null;
    protected ?string $icon = null;
    protected ?string $iconPosition = 'left';
    protected $handleCallback = null;
    protected array $props = [];

    public static function make(string $name): self
    {
        $instance = new self();
        $instance->name = $name;

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

    public function getLabel(): ?string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->name));
    }

    public function styles(string $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    public function getStyles(): ?string
    {
        return $this->styles;
    }

    public function icon(string $icon, string $position = 'left'): self
    {
        $this->icon = $icon;
        $this->iconPosition = $position;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
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

    public function handle(callable $callback): self
    {
        $this->handleCallback = $callback;

        return $this;
    }

    public function getHandleCallback(): ?callable
    {
        return $this->handleCallback;
    }

    public function execute(array $ids): mixed
    {
        if ($this->handleCallback === null) {
            return null;
        }

        return call_user_func($this->handleCallback, $ids);
    }

    public function toArray(): array
    {
        return [
            'type' => 'action',
            'name' => $this->name,
            'label' => $this->getLabel(),
            'styles' => $this->styles,
            'icon' => $this->icon,
            'iconPosition' => $this->iconPosition,
            'props' => $this->props,
        ];
    }
}
