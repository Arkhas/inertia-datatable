<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

class ColumnAction
{
    protected string $name;
    protected ?string $label = null;
    protected ?string $icon = null;
    protected ?string $iconPosition = 'left';
    protected $handleCallback = null;
    protected array $props = [];
    protected $urlCallback = null;
    protected bool $separator = false;

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

    public function url(callable $callback): self
    {
        $this->urlCallback = $callback;

        return $this;
    }

    public function getUrlCallback(): ?callable
    {
        return $this->urlCallback;
    }

    public function hasUrlCallback(): bool
    {
        return $this->urlCallback !== null;
    }

    public function executeUrlCallback($model): ?string
    {
        if (!$this->hasUrlCallback()) {
            return null;
        }

        return call_user_func($this->urlCallback, $model);
    }

    public function separator(bool $separator = true): self
    {
        $this->separator = $separator;

        return $this;
    }

    public function hasSeparator(): bool
    {
        return $this->separator;
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
            'name' => $this->name,
            'label' => $this->getLabel(),
            'icon' => $this->icon,
            'iconPosition' => $this->iconPosition,
            'props' => $this->props,
            'hasUrlCallback' => $this->hasUrlCallback(),
            'separator' => $this->separator,
        ];
    }
}
