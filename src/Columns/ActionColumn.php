<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

use Illuminate\Database\Eloquent\Builder;

class ActionColumn extends Column
{
    protected $actionCallback = null;
    protected ?string $width= '40px';
    protected ?string $label = '';
    protected bool $exportable = false;


    public static function make(string $name = 'actions'): self
    {
        $instance = new self();
        $instance->name = $name;

        // Set sortable and searchable to false by default for action columns
        $instance->sortable = false;
        $instance->searchable = false;

        return $instance;
    }

    public function action($action): self
    {
        $this->actionCallback = $action;

        return $this;
    }

    /**
     * Check if the action has a confirmation callback.
     *
     * @return bool
     */
    public function hasConfirmCallback(): bool
    {
        if ($this->actionCallback instanceof ColumnAction) {
            return $this->actionCallback->hasConfirmCallback();
        }

        if ($this->actionCallback instanceof ColumnActionGroup) {
            foreach ($this->actionCallback->getActions() as $action) {
                if ($action->hasConfirmCallback()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAction()
    {
        return $this->actionCallback;
    }

    public function renderHtml(object $model): ?string
    {
        // For action columns, we don't need to render HTML
        // The actions will be rendered by the frontend
        return null;
    }

    public function toArray(): array
    {
        $columnData = [
            'name'         => $this->getName(),
            'label'        => $this->getLabel(),
            'hasIcon'      => $this->hasIcon(),
            'sortable'     => $this->isSortable(),
            'searchable'   => $this->isSearchable(),
            'toggable'     => $this->isToggable(),
            'iconPosition' => $this->getIconPosition() ?? 'left',
            'type'         => 'action',
            'action'       => $this->getAction()
        ];

        return $columnData;
    }
}
