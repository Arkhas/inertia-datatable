<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable;

use Arkhas\InertiaDatatable\Actions\TableActionGroup;
use Arkhas\InertiaDatatable\Actions\TableAction;
use Arkhas\InertiaDatatable\Columns\ActionColumn;
use Arkhas\InertiaDatatable\Columns\CheckboxColumn;
use Arkhas\InertiaDatatable\Columns\ColumnAction;
use Arkhas\InertiaDatatable\Columns\ColumnActionGroup;
use Arkhas\InertiaDatatable\Services\ExportService;
use Error;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function getSessionKey(string $suffix = ''): string
    {
        $baseKey = 'dt_' . md5(get_class($this));
        return $suffix ? "{$baseKey}_{$suffix}" : $baseKey;
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

        if (!$request->has('action') || !$request->has('ids')) {
            return null;
        }

        $actionName = $request->input('action');
        $ids = $request->input('ids');

        if (str_ends_with($actionName, '_confirm')) {
            return $this->handleConfirmation($actionName, $ids);
        }

        // Try table actions first
        foreach ($this->table->getActions() as $tableAction) {
            if ($tableAction instanceof TableActionGroup) {
                foreach ($tableAction->getActions() as $groupAction) {
                    if ($groupAction->getName() === $actionName) {
                        return $groupAction->execute($ids);
                    }
                }
            } elseif ($tableAction instanceof TableAction && $tableAction->getName() === $actionName) {
                return $tableAction->execute($ids);
            }
        }

        // For single record actions
        if (count($ids) !== 1) {
            return null;
        }

        $model = $this->table->getQuery()->find($ids[0]);
        if (!$model) {
            return null;
        }

        foreach ($this->table->getColumns() as $column) {
            if (!$column instanceof ActionColumn) {
                continue;
            }

            $colAction = $column->getAction();

            if ($colAction instanceof ColumnActionGroup) {
                foreach ($colAction->getActions() as $action) {
                    if ($action->getName() === $actionName) {
                        return $action->execute($model);
                    }
                }
            } elseif ($colAction instanceof ColumnAction && $colAction->getName() === $actionName) {
                return $colAction->execute($model);
            }
        }

        return null;
    }

    protected function handleConfirmation(string $actionName, array $ids): ?array
    {
        $baseAction = str_replace('_confirm', '', $actionName);

        // Try table actions
        foreach ($this->table->getActions() as $action) {
            if ($action instanceof TableActionGroup) {
                foreach ($action->getActions() as $groupAction) {
                    if ($groupAction->getName() === $baseAction) {
                        return ['confirmData' => $groupAction->getConfirmData($ids)];
                    }
                }
            } elseif ($action instanceof TableAction && $action->getName() === $baseAction) {
                return ['confirmData' => $action->getConfirmData($ids)];
            }
        }

        // Single record actions
        if (count($ids) !== 1) {
            return null;
        }

        $model = $this->table->getQuery()->find($ids[0]);
        if (!$model) {
            return null;
        }

        foreach ($this->table->getColumns() as $column) {
            if (!$column instanceof ActionColumn) {
                continue;
            }

            $columnAction = $column->getAction();

            if ($columnAction instanceof ColumnActionGroup) {
                foreach ($columnAction->getActions() as $action) {
                    if ($action->getName() === $baseAction) {
                        return ['confirmData' => $action->getConfirmData($model)];
                    }
                }
            } elseif ($columnAction instanceof ColumnAction && $columnAction->getName() === $baseAction) {
                return ['confirmData' => $columnAction->getConfirmData($model)];
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

    public function render(string $component): JsonResponse|Response|BinaryFileResponse
    {
        if (!isset($this->table)) {
            throw new Error('No table set for datatable');
        }

        $props = $this->getProps();

        $request = $this->getRequest();
        // Handle export if requested
        if ($request->has('export')) {
            return $this->handleExport();
        }

        return Inertia::render($component, $props);
    }

    /**
     * Store a value in the session for this datatable
     */
    public function storeInSession(string $key, $value): void
    {
        session()->put($this->getSessionKey($key), $value);
    }

    /**
     * Get a value from the session for this datatable
     */
    public function getFromSession(string $key, $default = null)
    {
        return session()->get($this->getSessionKey($key), $default);
    }

    public function getProps(): array|BinaryFileResponse
    {
        $req = $this->getRequest();

        // Handle state persistence
        $pageSize = $this->persistState('pageSize', $req->input('pageSize'), $this->defaultPageSize);
        $sort = $this->persistState('sort', $req->input('sort'));
        $direction = $this->persistState('direction', $req->input('direction'), 'asc');
        $visibleCols = $this->persistState('visibleColumns', $req->input('visibleColumns'));

        // Special handling for filters
        $filters = $req->input('filters');
        if ($filters !== null) {
            if (is_array($filters) && empty($filters)) {
                session()->forget($this->getSessionKey('filters'));
            } else {
                $this->storeInSession('filters', $filters);
            }
        } else {
            $filters = $this->getFromSession('filters', []);
        }

        return [
            'actionResult' => $this->handleAction(),
            'columns' => fn() => $this->getColumns(),
            'filters' => fn() => $this->getFilters(),
            'actions' => fn() => $this->getActions(),
            'data' => fn() => $this->getData(),
            'pageSize' => fn() => $pageSize,
            'availablePageSizes' => fn() => $this->availablePageSizes,
            'sort' => fn() => $sort,
            'direction' => fn() => $direction,
            'currentFilters' => fn() => $this->getCurrentFilterValues($filters),
            'translations' => fn() => $this->getTranslations(),
            'visibleColumns' => fn() => $visibleCols,
            'exportable' => fn() => $this->table->isExportable(),
            'exportType' => fn() => $this->table->getExportType(),
            'exportColumn' => fn() => $this->table->getExportColumn(),
        ];
    }

    private function persistState(string $key, $value, $default = null)
    {
        if ($value !== null) {
            $this->storeInSession($key, $value);
            return $value;
        }

        return $this->getFromSession($key, $default);
    }

    public function getTranslations(): array
    {
        $locale = config('app.locale', 'en');
        $result = [$locale => []];

        // Core translations
        $core = trans('inertia-datatable::messages', [], $locale);
        if (is_array($core)) {
            $result[$locale] = $this->i18nify($core);
        }

        // Custom translations (override core)
        $custom = trans('vendor/inertia-datatable/messages', [], $locale);
        if (is_array($custom) && !empty($custom)) {
            $result[$locale] = array_merge(
                $result[$locale],
                $this->i18nify($custom)
            );
        }

        return $result;
    }

    protected function i18nify(array $translations): array
    {
        $out = [];

        foreach ($translations as $key => $translation) {
            if (!is_string($translation)) {
                $out[$key] = $translation;
                continue;
            }

            // Convert Laravel :var to i18next {{var}}
            $out[$key] = preg_replace('/:(\w+)/', '{{$1}}', $translation);
        }

        return $out;
    }

    public function getColumns(): array
    {
        $columns = [];
        foreach ($this->table->getColumns() as $column) {
            $columnData = method_exists($column, 'toArray') ? $column->toArray() : [
                'name'       => $column->getName(),
                'label'      => $column->getLabel(),
                'hasIcon'    => method_exists($column, 'hasIcon') ? $column->hasIcon() : (method_exists($column, 'getIconCallback') && $column->getIconCallback() !== null),
                'sortable'   => method_exists($column, 'isSortable') ? $column->isSortable() : true,
                'searchable' => method_exists($column, 'isSearchable') ? $column->isSearchable() : true,
                'toggable'   => method_exists($column, 'isToggable') ? $column->isToggable() : true,
                'iconPosition' => method_exists($column, 'getIconPosition') ? $column->getIconPosition() : 'left'
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
                'multiple' => $filter->isMultiple(),
                'filterOptions' => method_exists($filter, 'getFilterOptions') ? $filter->getFilterOptions() : []
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
        $query             = $this->table->getQuery()->clone();

        $searchTerm = $request->input('search');

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm, $columns) {
                foreach ($columns as $column) {
                    // Only include searchable columns
                    if (!$column->isSearchable()) {
                        continue;
                    }

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
            $this->storeInSession('filters', $filters);
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

        $collection = $data->getCollection();
        $processedData = collect();

        foreach ($collection as $model) {
            $result = [];


            foreach ($columns as $column) {
                $columnName = $column->getName();

                // Handle HTML rendering
                if (method_exists($column, 'renderHtml')) {
                    $result["{$columnName}"] = $column->renderHtml($model);
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
                }

                // Handle action columns
                if ($column instanceof ActionColumn) {
                    $action = $column->getAction();
                    // Convert ColumnActionGroup to array if needed
                    if ($action instanceof ColumnActionGroup) {
                        // Get the action group data
                        $actionGroupArray = $action->toArray($model);
                        $result["{$columnName}_action"] = $actionGroupArray;
                    } elseif ($action instanceof ColumnAction) {
                        // Convert ColumnAction to array with actions property
                        $actionArray = $action->toArray($model);
                        if ($action->hasUrlCallback()) {
                            $actionArray['url'] = $action->executeUrlCallback($model);
                        }
                        // Wrap the action in an actions array so the frontend recognizes it as a single action
                        $result["{$columnName}_action"] = [
                            'actions' => [$actionArray]
                        ];
                    }
                }
            }
            $result['_id'] = $model->getKey();

            $processedData->push($result);
        }
        $data->setCollection($processedData);

        return $data;
    }

    public function getTable(): EloquentTable
    {
        return $this->table;
    }

    /**
     * Handle export request
     */
    protected function handleExport(): BinaryFileResponse
    {
        $request = $this->getRequest();

        // Check if the table is exportable
        if (!$this->table->isExportable()) {
            abort(403, 'This table is not exportable');
        }

        // Get export parameters
        $exportType = $request->input('exportType', $this->table->getExportType());
        $exportColumns = $request->input('exportColumns', $this->table->getExportColumn());
        $exportRows = $request->input('exportRows', 'all');
        $selectedIds = $request->input('selectedIds', '');

        // Create export service
        $exportService = new ExportService($this->getResults(), $this->table);

        // Set the export type from the request
        $exportService->withExportType($exportType);

        // Set selected IDs if exporting only selected rows
        if ($exportRows === 'selected' && !empty($selectedIds)) {
            $exportService->withSelectedIds(explode(',', $selectedIds));
        }

        // Pass visible columns information if exporting only visible columns
        if ($exportColumns === 'visible') {
            $visibleColumns = $this->getFromSession('visibleColumns');
            $exportService->withVisibleColumns($visibleColumns);
        }

        // Generate filename
        $filename = $this->table->getExportName();

        // Return the export file
        return $exportService->export($filename);
    }
}
