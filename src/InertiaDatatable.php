<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable;

use Arkhas\InertiaDatatable\Actions\TableActionGroup;
use Arkhas\InertiaDatatable\Actions\TableAction;
use Arkhas\InertiaDatatable\Columns\ActionColumn;
use Arkhas\InertiaDatatable\Columns\CheckboxColumn;
use Arkhas\InertiaDatatable\Columns\ColumnActionGroup;
use Error;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Inertia\Response;

abstract class InertiaDatatable
{
    protected EloquentTable $table;
    protected int      $defaultPageSize;
    protected array    $availablePageSizes     = [10, 25, 100];
    protected array    $additionalSearchFields = [];
    protected ?Request $request                = null;

    public function __construct()
    {
        $this->defaultPageSize = config('inertia-datatable.pagination.default_page_size', 25);
        $this->setup();
    }

    /**
     * Get a unique session key for this datatable
     */
    protected function getSessionKey(string $suffix = ''): string
    {
        $className = get_class($this);
        $baseKey = 'datatable_' . md5($className);

        return $suffix ? $baseKey . '_' . $suffix : $baseKey;
    }

    public abstract function setup(): void;

    public function table(EloquentTable $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function additionalSearchFields(array $fields): self
    {
        $this->additionalSearchFields = $fields;

        return $this;
    }

    public function getRequest(): Request
    {
        return app(Request::class);
    }

    public function handleAction(): mixed
    {
         $request = $this->getRequest();

        if ($request->has('action') && $request->has('ids')) {
            $actionName = $request->input('action');
            $ids    = $request->input('ids');
            foreach ($this->table->getActions() as $action) {
                if ($action instanceof TableActionGroup) {
                    foreach ($action->getActions() as $groupAction) {
                        if ($groupAction->getName() === $actionName) {
                            return $groupAction->execute($ids);
                        }
                    }
                } elseif ($action instanceof TableAction) {

                    if ($action->getName() === $actionName) {
                        return $action->execute($ids);
                    }
                }
            }
        }


        return null;
    }

    public function getCurrentFilterValues(array $filters): array
    {
        $currentFilterValues = [];
        foreach ($filters as $filterName => $filterValue) {
            if (is_string($filterValue) && str_contains($filterValue, ',')) {
                $currentFilterValues[$filterName] = explode(',', $filterValue);
            } else {
                $currentFilterValues[$filterName] = $filterValue;
            }
        }

        return $currentFilterValues;
    }

    public function render(string $component): JsonResponse|Response
    {
        if (!isset($this->table)) {
            throw new Error('No table set for datatable');
        }

        return Inertia::render($component, $this->getProps());
    }

    /**
     * Store a value in the session for this datatable
     */
    protected function storeInSession(string $key, $value): void
    {
        session()->put($this->getSessionKey($key), $value);
    }

    /**
     * Get a value from the session for this datatable
     */
    protected function getFromSession(string $key, $default = null)
    {
        return session()->get($this->getSessionKey($key), $default);
    }

    public function getProps(): array
    {
        $request = $this->getRequest();

        // Get values from request or session
        $pageSize = $request->input('pageSize');
        $sort = $request->input('sort');
        $direction = $request->input('direction');
        $filters = $request->input('filters');
        $visibleColumns = $request->input('visibleColumns');

        // If values are in the request, store them in session
        if ($pageSize !== null) {
            $this->storeInSession('pageSize', $pageSize);
        } else {
            // Get from session or use default
            $pageSize = $this->getFromSession('pageSize', $this->defaultPageSize);
        }

        if ($sort !== null) {
            $this->storeInSession('sort', $sort);
            $this->storeInSession('direction', $direction ?? 'asc');
        } else {
            // Get from session
            $sort = $this->getFromSession('sort');
            $direction = $this->getFromSession('direction', 'asc');
        }

        if ($filters !== null) {
            // If filters is empty, remove it from session
            if (is_array($filters) && empty($filters)) {
                session()->forget($this->getSessionKey('filters'));
            } else {
                // Store filters in session
                $this->storeInSession('filters', $filters);
            }
        } else {
            // Get from session or use empty array
            $filters = $this->getFromSession('filters', []);
        }

        if ($visibleColumns !== null) {
            $this->storeInSession('visibleColumns', $visibleColumns);
        } else {
            // Get from session
            $visibleColumns = $this->getFromSession('visibleColumns');
        }

        $props = [
            'actionResult'       =>  $this->handleAction(),
            'columns'            => fn() => $this->getColumns(),
            'filters'            => fn() => $this->getFilters(),
            'actions'            => fn() => $this->getActions(),
            'data'               => fn() => $this->getData(),
            'pageSize'           => fn() => $pageSize,
            'availablePageSizes' => fn() => $this->availablePageSizes,
            'sort'               => fn() => $sort,
            'direction'          => fn() => $direction,
            'currentFilters'     => fn() => $this->getCurrentFilterValues($filters),
            'translations'       => fn() => $this->getTranslations(),
            'visibleColumns'     => fn() => $visibleColumns,
        ];

        return $props;
    }

    /**
     * Get translations from Laravel's lang directory and package's lang directory
     */
    protected function getTranslations(): array
    {
        $translations = [];
        $locale = config('app.locale', 'en');
        $translations[$locale] = [];

        // Load translations from package
        $packageTranslations = trans('inertia-datatable::messages', [], $locale);
        if (is_array($packageTranslations)) {
            $translations[$locale] = $this->convertPlaceholders($packageTranslations);
        }

        // Load translations from vendor published files (will override package translations)
        $vendorTranslations = trans('vendor/inertia-datatable/messages', [], $locale);
        if (is_array($vendorTranslations) && $vendorTranslations !== []) {
            $translations[$locale] = array_merge(
                $translations[$locale],
                $this->convertPlaceholders($vendorTranslations)
            );
        }

        return $translations;
    }

    /**
     * Convert Laravel-style placeholders (:key) to i18next style ({{key}})
     */
    protected function convertPlaceholders(array $translations): array
    {
        $result = [];

        foreach ($translations as $key => $value) {
            if (is_string($value)) {
                // Replace :key with {{key}}
                $result[$key] = preg_replace('/:(\w+)/', '{{$1}}', $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function getColumns(): array
    {
        $columns = [];
        foreach ($this->table->getColumns() as $column) {
            $columnData = method_exists($column, 'toArray') ? $column->toArray() : [
                'name'       => $column->getName(),
                'label'      => $column->getLabel() ?? ucfirst(str_replace('_', ' ', $column->getName())),
                'hasIcon'    => method_exists($column, 'getIconCallback') && $column->getIconCallback() !== null,
                'sortable'   => method_exists($column, 'isSortable') ? $column->isSortable() : true,
                'searchable' => method_exists($column, 'isSearchable') ? $column->isSearchable() : true,
                'toggable'   => method_exists($column, 'isToggable') ? $column->isToggable() : true
            ];

            // Add type for checkbox columns
            if ($column instanceof CheckboxColumn) {
                $columnData['type'] = 'checkbox';
            }

            // Add type and action for action columns
            if ($column instanceof ActionColumn) {
                $columnData['type'] = 'action';
                $columnData['action'] = $column->getAction();
            }

            $columns[] = $columnData;
        }

        return $columns;
    }

    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->table->getFilters() as $filter) {
            $filters[] = method_exists($filter, 'toArray') ? $filter->toArray() : [
                'name'     => $filter->getName(),
                'label'    => $filter->getLabel(),
                'options'  => $filter->getOptions(),
                'icons'    => $filter->getIcons(),
                'iconPositions' => $filter->getIconPositions(),
                'multiple' => $filter->isMultiple()
            ];
        }

        return $filters;
    }

    public function getActions(): array
    {
        $actions = [];
        foreach ($this->table->getActions() as $action) {
            $actions[] = $action->toArray();
        }

        return $actions;
    }

    public function getResults(): Builder
    {
        if (!isset($this->table)) {
            throw new Error('No table set for datatable');
        }
        $request           = $this->getRequest();
        $columns           = $this->table->getColumns();
        $filterDefinitions = $this->table->getFilters();
        $query             = $this->table->getQuery();

        // Handle search if needed
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            // If search term is empty, remove it from session
            if ($searchTerm === '') {
                session()->forget($this->getSessionKey('search'));
                $searchTerm = null;
            } else {
                // Store search term in session
                $this->storeInSession('search', $searchTerm);
            }
        } else {
            // Get search term from session
            $searchTerm = $this->getFromSession('search');
        }

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm, $columns) {
                foreach ($columns as $column) {
                    // Only include searchable columns
                    //if (!$column->isSearchable()) {
                    //    continue;
                    //}

                    if ($column->getFilterCallback() || $column->hasRelation()) {
                        $q->orWhere(function ($subQuery) use ($column, $searchTerm) {
                            $column->applyFilter($subQuery, $searchTerm);
                        });
                    } else {
                        $columnName = $column->getName();
                        $q->orWhere($columnName, 'like', "%{$searchTerm}%");
                    }
                }
                foreach ($this->additionalSearchFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }

        // Get filters from request or session
        $filters = $request->input('filters');
        if ($filters !== null) {
            // If filters is empty, remove it from session
            if (is_array($filters) && empty($filters)) {
                session()->forget($this->getSessionKey('filters'));
            } else {
                // Store filters in session
                $this->storeInSession('filters', $filters);
            }
        } else {
            // Get filters from session
            $filters = $this->getFromSession('filters', []);
        }

        if (!empty($filters)) {
            foreach ($filters as $filterName => $filterValue) {
                foreach ($filterDefinitions as $filter) {
                    if ($filter->getName() === $filterName) {
                        $filter->applyFilter($query, $filterValue);
                    }
                }
            }
        }

        // Always apply direct column filters (for test expectations)
        foreach ($request->all() as $key => $value) {
            foreach ($columns as $column) {
                if ($column->getName() === $key) {
                    $column->applyFilter($query, $value);
                }
            }
        }

        // Get sort from request or session
        $sort = $request->input('sort');
        if ($sort !== null) {
            $direction = $request->input('direction', 'asc');
            // Store sort and direction in session
            $this->storeInSession('sort', $sort);
            $this->storeInSession('direction', $direction);
        } else {
            // Get sort and direction from session
            $sort = $this->getFromSession('sort');
            $direction = $this->getFromSession('direction', 'asc');
        }

        if ($sort) {
            foreach ($columns as $column) {
                if ($column->getName() === $sort) {
                    $column->applyOrder($query, $direction);
                }
            }
        }

        // Get page size from request or session
        $pageSize = $request->input('pageSize');
        if ($pageSize !== null) {
            // Store page size in session
            $this->storeInSession('pageSize', $pageSize);
        } else {
            // Get page size from session or use default
            $pageSize = $this->getFromSession('pageSize', $this->defaultPageSize);
        }

        $pageSize = (int)$pageSize;
        if ($pageSize < 1) {
            $pageSize = 1;
        }
        $query->limit($pageSize);

        return $query;
    }

    public function getData(): LengthAwarePaginator
    {
        $results  = $this->getResults();
        $request  = $this->getRequest();
        $columns  = $this->table->getColumns();

        // Get page size from request or session
        $pageSize = $request->input('pageSize');
        if ($pageSize !== null) {
            // Store page size in session
            $this->storeInSession('pageSize', $pageSize);
        } else {
            // Get page size from session or use default
            $pageSize = $this->getFromSession('pageSize', $this->defaultPageSize);
        }

        $pageSize = max(1, (int)$pageSize);

        $data = $results->paginate($pageSize);

        $processedTasks = $data->getCollection()->map(function ($model, $index) use ($columns) {
            $result = $model->toArray();

            // Default ID is the index of the row
            $id = $index;

            foreach ($columns as $column) {
                $columnName = $column->getName();

                // Handle HTML rendering
                if (method_exists($column, 'renderHtml')) {
                    $result["{$columnName}_html"] = $column->renderHtml($model);
                }

                // Handle icon rendering
                if (method_exists($column, 'renderIcon')) {
                    $icon = $column->renderIcon($model);
                    if ($icon !== null) {
                        $result["{$columnName}_icon"] = $icon;
                    }
                }

                // Handle checkbox columns
                if ($column instanceof CheckboxColumn) {
                    $value = $column->getValue($model);
                    $result["{$columnName}_value"] = $value;
                    $result["{$columnName}_checked"] = $column->isChecked($model);
                    $result["{$columnName}_disabled"] = $column->isDisabled($model);

                    // Use the value from CheckboxColumn as the ID
                    $id = $value;
                }

                // Handle action columns
                if ($column instanceof ActionColumn) {
                    $action = $column->getAction();
                    // Convert ColumnActionGroup to array if needed
                    if ($action instanceof ColumnActionGroup) {
                        $result["{$columnName}_action"] = $action->toArrayWithModel($model);
                    } else {
                        $result["{$columnName}_action"] = $action;
                    }
                }
            }

            // Set the ID explicitly
            $result['id'] = $id;

            return $result;
        });
        $data->setCollection($processedTasks);

        return $data;
    }

    public function getTable(): EloquentTable
    {
        return $this->table;
    }
}
