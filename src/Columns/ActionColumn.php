<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Columns;

use Illuminate\Database\Eloquent\Builder;

class ActionColumn extends Column
{
    protected $actionCallback = null;
    protected ?string $width= '40px';
    protected ?string $label = '';


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
}
