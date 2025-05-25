<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

use Arkhas\InertiaDatatable\Traits\HasIcon;
use Illuminate\Database\Eloquent\Model;

class ColumnAction
{
    use HasIcon;

    protected $confirmCallback = null;

    protected string $name;
    protected ?string $label = null;
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

    public function execute(Model $model): mixed
    {
        return $this->handleCallback ? call_user_func($this->handleCallback, $model) : null;
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
     * @param mixed $model
     * @return array
     */
    protected function addConfirmToArray(array $array, $model = null): array
    {
        $array['hasConfirmCallback'] = $this->hasConfirmCallback();

        return $array;
    }

    public function toArray($model = null): array
    {
        $array = [
            'name' => $this->name,
            'label' => $this->getLabel(),
            'hasIcon' => $this->hasIcon(),
            'icon' => $model !== null ? $this->renderIcon($model) : $this->getIcon(),
            'iconPosition' => $this->getIconPosition(),
            'props' => $this->props,
            'hasUrlCallback' => $this->hasUrlCallback(),
            'separator' => $this->separator,
        ];

        return $this->addConfirmToArray($array, $model);
    }

    /**
     * Get the confirmation data for the given model.
     *
     * @param mixed $model
     * @return array|null
     */
    public function getConfirmData($model): ?array
    {
        if (!$this->hasConfirmCallback()) {
            return null;
        }

        return call_user_func($this->confirmCallback, $model);
    }
}
