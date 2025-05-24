<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Traits;

trait HasIcon
{
    protected ?string $icon = null;
    protected ?string $iconPosition = 'left';
    protected $iconCallback = null;

    /**
     * Set the icon for this element.
     * 
     * @param string|callable $icon The icon name or a callback that returns the icon name
     * @param string $position The position of the icon ('left' or 'right')
     * @return $this
     */
    public function icon(string|callable $icon, string $position = 'left'): self
    {
        if (is_callable($icon)) {
            $this->iconCallback = $icon;
            $this->icon = null;
        } else {
            $this->icon = $icon;
            $this->iconCallback = null;
        }
        
        $this->iconPosition = $position;

        return $this;
    }

    /**
     * Get the icon name.
     * 
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Get the icon callback.
     * 
     * @return callable|null
     */
    public function getIconCallback(): ?callable
    {
        return $this->iconCallback;
    }

    /**
     * Get the icon position.
     * 
     * @return string|null
     */
    public function getIconPosition(): ?string
    {
        return $this->iconPosition;
    }

    /**
     * Check if this element has an icon.
     * 
     * @return bool
     */
    public function hasIcon(): bool
    {
        return $this->icon !== null || $this->iconCallback !== null;
    }

    /**
     * Render the icon for a specific model.
     * 
     * @param object|null $model The model to render the icon for
     * @return string|null
     */
    public function renderIcon(?object $model = null): ?string
    {
        if ($this->iconCallback !== null && $model !== null) {
            return call_user_func($this->iconCallback, $model);
        }

        return $this->icon;
    }
}