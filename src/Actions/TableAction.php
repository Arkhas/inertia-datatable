<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Actions;

class TableAction
{
    protected $confirmCallback = null;

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

    /**
     * Add a confirmation dialog before executing the action.
     *
     * @param callable $callback A callback that returns an array with the following keys:
     *                          - title: The title of the confirmation dialog
     *                          - message: The message of the confirmation dialog
     *                          - confirm: The text of the confirm button
     *                          - cancel: The text of the cancel button
     *                          - disabled: Whether the confirm button should be disabled
     * @return $this
     */
    public function confirm(callable $callback): self
    {
        $this->confirmCallback = $callback;

        return $this;
    }

    /**
     * Check if the action has a confirmation callback.
     *
     * @return bool
     */
    public function hasConfirmCallback(): bool
    {
        return $this->confirmCallback !== null;
    }

    /**
     * Add the confirmation data to the array.
     *
     * @param array $array
     * @param array $ids
     * @return array
     */
    protected function addConfirmToArray(array $array, array $ids = []): array
    {
        $array['hasConfirmCallback'] = $this->hasConfirmCallback();

        if ($this->hasConfirmCallback()) {
            $array['confirmData'] = call_user_func($this->confirmCallback, $ids);
        }

        return $array;
    }

    public function toArray(): array
    {
        $array = [
            'type' => 'action',
            'name' => $this->name,
            'label' => $this->getLabel(),
            'styles' => $this->styles,
            'icon' => $this->icon,
            'iconPosition' => $this->iconPosition,
            'props' => $this->props,
        ];

        return $this->addConfirmToArray($array);
    }

    /**
     * Get the confirmation data for the given IDs.
     *
     * @param array $ids
     * @return array|null
     */
    public function getConfirmData(array $ids): ?array
    {
        if (!$this->hasConfirmCallback()) {
            return null;
        }

        return call_user_func($this->confirmCallback, $ids);
    }
}
