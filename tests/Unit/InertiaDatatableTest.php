<?php
namespace Tests\Unit;

use Arkhas\InertiaDatatable\Columns\ActionColumn;
use Arkhas\InertiaDatatable\Columns\ColumnAction;
use Arkhas\InertiaDatatable\Columns\ColumnActionGroup;
use Arkhas\InertiaDatatable\Filters\Filter;
use Arkhas\InertiaDatatable\Filters\FilterOption;
use Tests\TestCase;
use Tests\TestModels\TestModelDataTable;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;
use Arkhas\InertiaDatatable\InertiaDatatable;
use Arkhas\InertiaDatatable\EloquentTable;
use Arkhas\InertiaDatatable\Columns\Column;
use Arkhas\InertiaDatatable\Columns\CheckboxColumn;
use Arkhas\InertiaDatatable\Actions\TableAction;
use Arkhas\InertiaDatatable\Actions\TableActionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class InertiaDatatableTest extends TestCase
{
    use WithTestModels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestModels();
    }

    protected function tearDown(): void
    {
        $this->tearDownTestModels();
        parent::tearDown();
    }

    public function test_can_render_datatable_with_columns()
    {

        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ]);

        $datatable->table($table);

        $this->assertCount(2, $datatable->getTable()->getColumns());
    }

    public function test_render_without_filters_or_sorting()
    {
        $datatable = new TestModelDataTable();
        $query     = TestModel::query();
        $table     = EloquentTable::make($query)->columns([
            Column::make('name'),
            Column::make('status'),
        ]);

        $datatable->table($table);

        // Render without any filters or sorting
        $datatable->render('Datatable');

        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $query->pluck('name')->toArray());
    }

    public function test_render_with_invalid_sort_column()
    {
        $datatable = new TestModelDataTable();

        $query = TestModel::query();
        $table = EloquentTable::make($query)->columns([
            Column::make('name'),
            Column::make('status'),
        ]);

        $datatable->table($table);

        // Apply invalid sort column
        request()->replace(['sort' => 'invalid_column', 'direction' => 'asc']);
        $datatable->render('Datatable');

        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $query->pluck('name')->toArray());
    }

    public function test_render_with_no_matching_filters()
    {
        $datatable = new TestModelDataTable();

        $query = TestModel::query();
        $table = EloquentTable::make($query)->columns([
            Column::make('name'),
            Column::make('status'),
        ]);

        $datatable->table($table);

        // Apply a filter that does not match any column
        request()->merge(['nonexistent' => 'value']);
        $datatable->render('Datatable');

        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $query->pluck('name')->toArray());
    }

    public function test_apply_filter_when_not_searchable()
    {
        $datatable = new TestModelDataTable();

        // Create a query that directly filters for active status
        $query = TestModel::query();
        $table = EloquentTable::make($query)->columns([
            Column::make('name')->searchable(false),
        ]);

        $datatable->table($table);

        // Verify that the searchable flag is correctly set
        $columns = $datatable->getColumns();
        $this->assertFalse($columns[0]['searchable']);

        // Now add a searchable column and verify it works
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name')->searchable(false),
            Column::make('status')->searchable(true),
        ]);

        $datatable->table($table);

        // Verify that the searchable flags are correctly set
        $columns = $datatable->getColumns();
        $this->assertFalse($columns[0]['searchable']);
        $this->assertTrue($columns[1]['searchable']);

        // Test with a search term that matches the searchable column
        request()->replace(['search' => 'active']);
        $results = $datatable->getResults()->get();

        // Should find results since the status column is searchable
        $this->assertNotEmpty($results);
        $this->assertTrue($results->contains('status', 'active'));

        // Test with a search term that matches the non-searchable column
        // First, let's verify that Alice exists in the database
        $allResults = TestModel::query()->get();
        $this->assertTrue($allResults->contains('name', 'Alice'));

        // Now search for Alice with the name column not searchable
        request()->replace(['search' => 'Alice']);
        $results = $datatable->getResults()->get();

        // Should not find any results since 'Alice' only appears in the non-searchable column
        $this->assertEmpty($results);
    }

    public function test_get_current_filter_values_with_comma_separated_string()
    {
        $datatable = new TestModelDataTable();
        $filters   = [
            'status' => 'active,inactive',
            'name'   => 'Alice',
        ];
        $result    = $datatable->getCurrentFilterValues($filters);
        $this->assertEquals(['active', 'inactive'], $result['status']);
        $this->assertEquals('Alice', $result['name']);
    }

    public function test_get_props_returns_expected_keys()
    {

        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);
        $props = $datatable->getProps();
        $this->assertArrayHasKey('columns', $props);
        $this->assertArrayHasKey('filters', $props);
        $this->assertArrayHasKey('data', $props);
        $this->assertArrayHasKey('pageSize', $props);
        $this->assertArrayHasKey('availablePageSizes', $props);
        $this->assertArrayHasKey('sort', $props);
        $this->assertArrayHasKey('direction', $props);
        $this->assertArrayHasKey('currentFilters', $props);
    }

    public function test_get_columns_returns_expected_format()
    {

        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name')->label('Nom'),
        ]);
        $datatable->table($table);
        $columns = $datatable->getColumns();
        $this->asserttrue(!array_diff(
            [
                'name'         => 'name',
                'label'        => 'Nom',
                'hasIcon'      => false,
                'sortable'     => true,
                'toggable'     => true,
                'iconPosition' => 'left',
                'searchable'   => true,
            ], $columns[0]));
    }

    public function test_get_filters_returns_expected_format()
    {

        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->filters([]);
        $datatable->table($table);
        $filters = $datatable->getFilters();
        $this->assertIsArray($filters);
    }

    public function test_get_results_with_search_and_sort()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ]);
        $datatable->table($table);
        request()->replace(['search' => 'Alice', 'sort' => 'name', 'direction' => 'desc', 'pageSize' => 2]);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('name', 'Alice'));
    }

    public function test_get_data_returns_paginated_collection()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);
        request()->replace(['pageSize' => 2]);
        $data = $datatable->getData();
        $this->assertEquals(2, $data->perPage());
        $this->assertTrue($data->total() >= 3);
    }

    public function test_render_throws_error_without_table()
    {
        $datatable = new TestModelDataTable();
        $this->expectException(\Error::class);
        $datatable->render('Datatable');
    }

    public function test_get_results_throws_error_without_table()
    {
        $datatable = new TestModelDataTable();
        $this->expectException(\Error::class);
        $datatable->getResults();
    }

    public function test_get_results_applies_filter_and_direct_column_filter()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ]);
        $datatable->table($table);
        // Simulates a filter on status and a direct filter on name
        request()->replace(['filters' => ['status' => 'active'], 'name' => 'Alice']);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('name', 'Alice'));
        $this->assertTrue($results->contains('status', 'active'));
    }

    public function test_get_results_applies_filter_with_callback_and_direct_column_filter()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ])->filters([
            Filter::make('status')
                  ->options(['active', 'inactive'])
                  ->query(fn($query, $value) => $query->where('status', $value))
        ]);
        $datatable->table($table);
        // Simulates a filter on status and a direct filter on name
        request()->replace(['filters' => ['status' => 'active'], 'name' => 'Alice']);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('name', 'Alice'));
        $this->assertTrue($results->contains('status', 'active'));
    }

    public function test_get_results_with_invalid_page_size_sets_minimum()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);
        request()->replace(['pageSize' => 0]);
        $results = $datatable->getResults()->get();
        $this->assertNotEmpty($results);
    }

    public function test_get_data_adds_html_and_icon_keys()
    {
        $datatable = new TestModelDataTable();
        $column    = Column::make('name')
                           ->html(fn($model) => '<b>' . $model->name . '</b>')
                           ->icon(fn($model) => $model->name === 'Alice' ? 'icon' : null);
        $table     = EloquentTable::make(TestModel::query())->columns([$column]);
        $datatable->table($table);
        request()->replace(['pageSize' => 2]);
        $data  = $datatable->getData();
        $first = $data->items()[0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('name_icon', $first);
    }

    public function test_get_columns_label_fallback()
    {

        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('foo_bar'),
        ]);
        $datatable->table($table);
        $columns = $datatable->getColumns();
        $this->assertEquals('Foo bar', $columns[0]['label']);
    }

    public function test_get_filters_with_multiple_and_icons()
    {

        $datatable = new TestModelDataTable();
        $filter    = Filter::make('status', 'Status')
                           ->options(['active', 'inactive'])
                           ->icons(['icon1', 'icon2'])
                           ->multiple();
        $table     = EloquentTable::make(TestModel::query())->filters([$filter]);
        $datatable->table($table);
        $filters = $datatable->getFilters();
        $this->assertEquals('status', $filters[0]['name']);
        $this->assertEquals(['icon1', 'icon2'], $filters[0]['icons']);
        $this->assertTrue($filters[0]['multiple']);
    }

    public function test_get_filters_with_filter_options_and_count()
    {
        $datatable = new TestModelDataTable();

        // Create filter options with count
        $opt1 = FilterOption::make('active')->label('Active')->count(function () {
            return 5;
        });
        $opt2 = FilterOption::make('inactive')->label('Inactive')->count(function () {
            return 3;
        });

        $filter = Filter::make('status', 'Status')
                        ->options([$opt1, $opt2]);

        $table = EloquentTable::make(TestModel::query())->filters([$filter]);
        $datatable->table($table);

        $filters = $datatable->getFilters();

        // Check that filterOptions are included in the output
        $this->assertArrayHasKey('filterOptions', $filters[0]);
        $this->assertCount(2, $filters[0]['filterOptions']);

        // Check that count is included in each filter option
        $this->assertEquals(5, $filters[0]['filterOptions'][0]['count']);
        $this->assertEquals(3, $filters[0]['filterOptions'][1]['count']);
    }

    public function test_get_request_returns_app_instance_if_not_set()
    {
        $datatable = new TestModelDataTable();
        // We don't call setRequest, so getRequest should return app(Request::class)
        $request = $datatable->getProps()['pageSize'](); // getProps uses getRequest
        $this->assertNotNull($request);
    }

    public function test_get_results_applies_additional_search_fields()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table)->additionalSearchFields(['status']);
        request()->replace(['search' => 'active']);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('status', 'active'));
    }

    public function test_get_results_applies_no_filter_if_name_not_found()
    {
        $datatable = new TestModelDataTable();
        // Uses the make method to instantiate Filter correctly
        $filter = Filter::make('not_status', 'Not Status');
        $table  = EloquentTable::make(TestModel::query())->filters([$filter]);
        $datatable->table($table);
        request()->replace(['filters' => ['status' => 'active']]);
        $datatable->getResults();
        $this->assertTrue(true); // Si pas d'exception, le test passe
    }

    public function test_get_results_or_where_without_filter_callback()
    {
        $datatable = new TestModelDataTable();

        $column = Column::make('name');
        $table  = EloquentTable::make(TestModel::query())->columns([$column]);
        $datatable->table($table);
        request()->replace(['search' => 'Alice']);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('name', 'Alice'));
    }

    public function test_search_with_relation_column()
    {
        $datatable = new TestModelDataTable();

        // Create a column with a relation
        $column = Column::make('user.name');
        $table  = EloquentTable::make(TestModel::query())->columns([$column]);
        $datatable->table($table);

        // Verify that the column has a relation
        $this->assertTrue($column->hasRelation());
        $this->assertEquals(['user'], $column->getRelationPath());

        // Search for a user name
        request()->replace(['search' => 'John']);
        $results = $datatable->getResults()->get();

        // Should find results since we're searching in the user relation
        $this->assertNotEmpty($results);

        // The result should include Alice, who belongs to John
        $this->assertTrue($results->contains('name', 'Alice'));
    }

    public function test_search_with_relation_and_filter_callback()
    {
        $datatable = new TestModelDataTable();

        // Create a column with both a relation and a filter callback
        $column = Column::make('user.name')
                        ->filter(function ($query, $value) {
                            $query->whereHas('user', function ($q) use ($value) {
                                $q->where('name', 'like', "%$value%");
                            });
                        });

        $table = EloquentTable::make(TestModel::query())->columns([$column]);
        $datatable->table($table);

        // Verify that the column has a relation
        $this->assertTrue($column->hasRelation());
        $this->assertEquals(['user'], $column->getRelationPath());

        // Verify that the column has a filter callback
        $this->assertNotNull($column->getFilterCallback());

        // Search for a user name
        request()->replace(['search' => 'John']);
        $results = $datatable->getResults()->get();

        // Should find results since we're searching in the user relation
        $this->assertNotEmpty($results);

        // The result should include Alice, who belongs to John
        $this->assertTrue($results->contains('name', 'Alice'));
    }

    public function test_get_results_or_where_with_filter_callback()
    {
        $datatable = new TestModelDataTable();

        $column = Column::make('name')->filter(fn($query, $value) => $query->where('name', 'like', "%$value%"));
        $table  = EloquentTable::make(TestModel::query())->columns([$column]);
        $datatable->table($table);
        request()->replace(['search' => 'Alice']);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('name', 'Alice'));
    }


    public function test_get_data_without_render_html_or_icon()
    {
        $datatable = new TestModelDataTable();
        // Uses a real Column without html/icon callback
        $column = Column::make('name');
        $table  = EloquentTable::make(TestModel::query())->columns([$column]);
        $datatable->table($table);
        request()->replace(['pageSize' => 2]);
        $data  = $datatable->getData();
        $first = $data->items()[0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayNotHasKey('name_icon', $first);
    }

    public function test_get_filters_with_minimal_filter()
    {

        $datatable = new TestModelDataTable();
        $filter    = Filter::make('status');
        $table     = EloquentTable::make(TestModel::query())->filters([$filter]);
        $datatable->table($table);
        $filters = $datatable->getFilters();
        $this->assertEquals('status', $filters[0]['name']);
        $this->assertEquals('Status', $filters[0]['label']);
        $this->assertEquals([], $filters[0]['options']);
        $this->assertEquals([], $filters[0]['icons']);
        $this->assertFalse($filters[0]['multiple']);
    }

    public function test_handle_action_with_table_action()
    {
        $datatable = new TestModelDataTable();

        $action = TableAction::make('test_action')->handle(function ($ids) {
            return ['processed' => $ids];
        });

        $table = EloquentTable::make(TestModel::query())->actions([$action]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'test_action',
            'ids'    => [1, 2, 3]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals(['processed' => [1, 2, 3]], $result);
    }

    public function test_handle_action_with_action_group()
    {
        $datatable = new TestModelDataTable();

        $groupAction = TableAction::make('group_action')->handle(function ($ids) {
            return ['group_processed' => $ids];
        });

        $actionGroup = TableActionGroup::make('test_group')->actions([$groupAction]);

        $table = EloquentTable::make(TestModel::query())->actions([$actionGroup]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'group_action',
            'ids'    => [4, 5, 6]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals(['group_processed' => [4, 5, 6]], $result);
    }

    public function test_handle_action_with_no_matching_action()
    {
        $datatable = new TestModelDataTable();

        $action = TableAction::make('test_action')->handle(function ($ids) {
            return ['processed' => $ids];
        });

        $table = EloquentTable::make(TestModel::query())->actions([$action]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'non_existent_action',
            'ids'    => [1, 2, 3]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertNull($result);
    }

    public function test_handle_action_without_action_param()
    {
        $datatable = new TestModelDataTable();

        $action = TableAction::make('test_action')->handle(function ($ids) {
            return ['processed' => $ids];
        });

        $table = EloquentTable::make(TestModel::query())->actions([$action]);
        $datatable->table($table);

        $request = new Request([
            'ids' => [1, 2, 3]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertNull($result);
    }

    public function test_handle_action_with_confirmation()
    {
        $datatable = new TestModelDataTable();

        $action = TableAction::make('test_action')
                             ->confirm(function ($ids) {
                                 return [
                                     'title'   => 'Confirm Action',
                                     'message' => 'Are you sure you want to perform this action?',
                                     'confirm' => 'Yes',
                                     'cancel'  => 'No'
                                 ];
                             });

        $table = EloquentTable::make(TestModel::query())->actions([$action]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'test_action_confirm',
            'ids'    => [1, 2, 3]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals([
            'confirmData' => [
                'title'   => 'Confirm Action',
                'message' => 'Are you sure you want to perform this action?',
                'confirm' => 'Yes',
                'cancel'  => 'No'
            ]
        ], $result);
    }

    public function test_handle_action_with_group_confirmation()
    {
        $datatable = new TestModelDataTable();

        $groupAction = TableAction::make('group_action')
                                  ->confirm(function ($ids) {
                                      return [
                                          'title'   => 'Confirm Group Action',
                                          'message' => 'Are you sure you want to perform this group action?',
                                          'confirm' => 'Yes',
                                          'cancel'  => 'No'
                                      ];
                                  });

        $actionGroup = TableActionGroup::make('test_group')->actions([$groupAction]);

        $table = EloquentTable::make(TestModel::query())->actions([$actionGroup]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'group_action_confirm',
            'ids'    => [4, 5, 6]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals([
            'confirmData' => [
                'title'   => 'Confirm Group Action',
                'message' => 'Are you sure you want to perform this group action?',
                'confirm' => 'Yes',
                'cancel'  => 'No'
            ]
        ], $result);
    }

    public function test_handle_confirmation_with_column_action()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 123, 'name' => 'Test Model']);

        $columnAction = ColumnAction::make('column_action')
                                    ->confirm(function ($model) {
                                        return [
                                            'title'   => 'Confirm Column Action',
                                            'message' => "Are you sure you want to perform this action on {$model->name}?",
                                            'confirm' => 'Yes',
                                            'cancel'  => 'No'
                                        ];
                                    });

        $columnActionGroup = ColumnActionGroup::make()
                                              ->actions([$columnAction]);

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnActionGroup);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'column_action_confirm',
            'ids'    => [123]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals([
            'confirmData' => [
                'title'   => 'Confirm Column Action',
                'message' => 'Are you sure you want to perform this action on Test Model?',
                'confirm' => 'Yes',
                'cancel'  => 'No'
            ]
        ], $result);
    }

    public function test_handle_confirmation_with_non_existent_action()
    {
        $datatable = new TestModelDataTable();

        $table = EloquentTable::make(TestModel::query());
        $datatable->table($table);

        $request = new Request([
            'action' => 'non_existent_action_confirm',
            'ids'    => [1, 2, 3]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertNull($result);
    }

    public function test_handle_action_with_column_action()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 123, 'name' => 'Test Model']);

        // Create a column action with a handle callback that returns the model's name
        $columnAction = ColumnAction::make('column_action')
                                    ->handle(function ($model) {
                                        return "Handled action for {$model->name}";
                                    });

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnAction);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'column_action',
            'ids'    => [123]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals('Handled action for Test Model', $result);
    }

    public function test_handle_action_with_column_action_group()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 456, 'name' => 'Group Test Model']);

        // Create a column action with a handle callback that returns the model's name
        $columnAction = ColumnAction::make('group_column_action')
                                    ->handle(function ($model) {
                                        return "Handled group action for {$model->name}";
                                    });

        $columnActionGroup = ColumnActionGroup::make()
                                              ->actions([$columnAction]);

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnActionGroup);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        $request = new Request([
            'action' => 'group_column_action',
            'ids'    => [456]
        ]);

        $this->app->instance(Request::class, $request);

        $result = $datatable->handleAction();
        $this->assertEquals('Handled group action for Group Test Model', $result);
    }

    public function test_get_translations()
    {
        Config::set('app.locale', 'en');

        // Mock the trans function to return test translations
        $this->app->instance('translator', new class {
            public function get($key, $replace = [], $locale = null)
            {
                if ($key === 'inertia-datatable::messages') {
                    return ['search' => 'Search :term', 'filter' => 'Filter'];
                } elseif ($key === 'vendor/inertia-datatable/messages') {
                    return ['search' => 'Custom Search :term', 'new_key' => 'New Value'];
                }

                return [];
            }
        });

        $datatable    = new TestModelDataTable();
        $translations = $datatable->getProps()['translations']();

        $this->assertArrayHasKey('en', $translations);
        $this->assertEquals('Custom Search {{term}}', $translations['en']['search']);
        $this->assertEquals('New Value', $translations['en']['new_key']);
        $this->assertEquals('Filter', $translations['en']['filter']);
    }

    public function test_get_translations_with_empty_vendor_translations()
    {
        Config::set('app.locale', 'en');

        // Mock the trans function to return test translations with empty vendor translations
        $this->app->instance('translator', new class {
            public function get($key, $replace = [], $locale = null)
            {
                if ($key === 'inertia-datatable::messages') {
                    return ['search' => 'Search :term', 'filter' => 'Filter'];
                } elseif ($key === 'vendor/inertia-datatable/messages') {
                    return []; // Empty array
                }

                return [];
            }
        });

        $datatable    = new TestModelDataTable();
        $translations = $datatable->getProps()['translations']();

        $this->assertArrayHasKey('en', $translations);
        $this->assertEquals('Search {{term}}', $translations['en']['search']);
        $this->assertEquals('Filter', $translations['en']['filter']);
    }

    public function test_get_translations_with_non_array_vendor_translations()
    {
        Config::set('app.locale', 'en');

        // Mock the trans function to return test translations with non-array vendor translations
        $this->app->instance('translator', new class {
            public function get($key, $replace = [], $locale = null)
            {
                if ($key === 'inertia-datatable::messages') {
                    return ['search' => 'Search :term', 'filter' => 'Filter'];
                } elseif ($key === 'vendor/inertia-datatable/messages') {
                    return 'Not an array'; // Not an array
                }

                return [];
            }
        });

        $datatable    = new TestModelDataTable();
        $translations = $datatable->getProps()['translations']();

        $this->assertArrayHasKey('en', $translations);
        $this->assertEquals('Search {{term}}', $translations['en']['search']);
        $this->assertEquals('Filter', $translations['en']['filter']);
    }

    public function test_get_translations_with_non_array_package_translations()
    {
        Config::set('app.locale', 'en');

        // Mock the trans function to return test translations with non-array package translations
        $this->app->instance('translator', new class {
            public function get($key, $replace = [], $locale = null)
            {
                if ($key === 'inertia-datatable::messages') {
                    return 'Not an array'; // Not an array
                } elseif ($key === 'vendor/inertia-datatable/messages') {
                    return ['search' => 'Custom Search :term', 'filter' => 'Filter'];
                }

                return [];
            }
        });

        $datatable    = new TestModelDataTable();
        $translations = $datatable->getProps()['translations']();

        $this->assertArrayHasKey('en', $translations);
        $this->assertEquals('Custom Search {{term}}', $translations['en']['search']);
        $this->assertEquals('Filter', $translations['en']['filter']);
    }

    public function test_convert_placeholders()
    {
        $datatable = new TestModelDataTable();

        // Use reflection to access protected method
        $reflectionMethod = new \ReflectionMethod(InertiaDatatable::class, 'i18nify');
        $reflectionMethod->setAccessible(true);

        $translations = [
            'search'          => 'Search :term',
            'filter'          => 'Filter by :field with :value',
            'no_placeholders' => 'No placeholders here',
            'nested'          => [
                'key' => 'Nested :value'
            ]
        ];

        $result = $reflectionMethod->invoke($datatable, $translations);

        $this->assertEquals('Search {{term}}', $result['search']);
        $this->assertEquals('Filter by {{field}} with {{value}}', $result['filter']);
        $this->assertEquals('No placeholders here', $result['no_placeholders']);
        $this->assertEquals('Nested :value', $result['nested']['key']); // The method doesn't process nested arrays recursively
    }

    public function test_get_columns_with_checkbox_column()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            CheckboxColumn::make('id'),
            Column::make('name')->label('Nom'),
        ]);
        $datatable->table($table);
        $columns = $datatable->getColumns();

        $this->assertEquals('checkbox', $columns[0]['type']);
    }

    public function test_get_actions_with_table_action()
    {
        $datatable = new TestModelDataTable();
        $action    = TableAction::make('edit')
                                ->label('Edit')
                                ->styles('primary')
                                ->icon('pencil')
                                ->props(['confirm' => true]);

        $table = EloquentTable::make(TestModel::query())->actions([$action]);
        $datatable->table($table);

        $actions = $datatable->getActions();

        $this->assertEquals([
            [
                'type'               => 'action',
                'name'               => 'edit',
                'label'              => 'Edit',
                'styles'             => 'primary',
                'icon'               => 'pencil',
                'iconPosition'       => 'left',
                'props'              => ['confirm' => true],
                'hasConfirmCallback' => false,
            ]
        ], $actions);
    }

    public function test_get_actions_with_action_group()
    {
        $datatable = new TestModelDataTable();

        $action1 = TableAction::make('edit')->label('Edit');
        $action2 = TableAction::make('delete')->label('Delete');

        $group = TableActionGroup::make('actions')
                                 ->label('Actions')
                                 ->styles('secondary')
                                 ->icon('menu')
                                 ->props(['dropdown' => true])
                                 ->actions([$action1, $action2]);

        $table = EloquentTable::make(TestModel::query())->actions([$group]);
        $datatable->table($table);

        $actions = $datatable->getActions();

        $this->assertEquals([
            [
                'type'         => 'group',
                'name'         => 'actions',
                'label'        => 'Actions',
                'styles'       => 'secondary',
                'icon'         => 'menu',
                'iconPosition' => 'left',
                'props'        => ['dropdown' => true],
                'actions'      => [
                    [
                        'type'               => 'action',
                        'name'               => 'edit',
                        'label'              => 'Edit',
                        'styles'             => null,
                        'icon'               => null,
                        'iconPosition'       => 'left',
                        'props'              => [],
                        'hasConfirmCallback' => false,
                    ],
                    [
                        'type'               => 'action',
                        'name'               => 'delete',
                        'label'              => 'Delete',
                        'styles'             => null,
                        'icon'               => null,
                        'iconPosition'       => 'left',
                        'props'              => [],
                        'hasConfirmCallback' => false,
                    ]
                ]
            ]
        ], $actions);
    }

    public function test_get_data_with_checkbox_column()
    {
        $datatable = new TestModelDataTable();


        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            CheckboxColumn::make()
                          ->checked(function ($model) {
                              return $model->status === 'active';
                          })
                          ->disabled(function ($model) {
                              return $model->name === 'Bob';
                          })
        ]);
        $datatable->table($table);

        request()->replace(['pageSize' => 10]);
        $data = $datatable->getData();

        // Check that we have the expected number of items
        $this->assertGreaterThanOrEqual(3, $data->total());

        // Check the first item (Alice)
        $alice = $data->getCollection()->firstWhere('name', 'Alice');

        $this->assertNotNull($alice);
        $this->assertArrayHasKey('checks_value', $alice);
        $this->assertArrayHasKey('checks_checked', $alice);
        $this->assertArrayHasKey('checks_disabled', $alice);

        // Alice should have ID as value, be checked (active), and not disabled
        $this->assertEquals($alice['_id'], $alice['checks_value']);
        $this->assertTrue($alice['checks_checked']);
        $this->assertFalse($alice['checks_disabled']);

        // Check Bob (should be disabled)
        $bob = $data->getCollection()->firstWhere('name', 'Bob');
        $this->assertNotNull($bob);
        $this->assertTrue($bob['checks_disabled']);
        $this->assertFalse($bob['checks_checked']); // Bob is inactive

        // Check Charlie (should be checked but not disabled)
        $charlie = $data->getCollection()->firstWhere('name', 'Charlie');
        $this->assertNotNull($charlie);
        $this->assertTrue($charlie['checks_checked']); // Charlie is active
        $this->assertFalse($charlie['checks_disabled']);
    }

    public function test_get_columns_with_action_column()
    {
        $datatable = new TestModelDataTable();

        $actionGroup = ColumnActionGroup::make()
                                        ->icon('Ellipsis')
                                        ->actions([
                                            ColumnAction::make('edit')
                                                        ->label('Edit')
                                                        ->icon('Edit')
                                        ]);

        $table = EloquentTable::make(TestModel::query())->columns([
            ActionColumn::make('actions')
                        ->label('Actions')
                        ->action($actionGroup),
            Column::make('name')->label('Nom'),
        ]);

        $datatable->table($table);
        $columns = $datatable->getColumns();

        $this->assertEquals('action', $columns[0]['type']);
    }

    public function test_get_data_with_action_column()
    {
        $datatable = new TestModelDataTable();

        // Create actions with URL callbacks
        $editAction = ColumnAction::make('edit')
                                  ->label('Edit')
                                  ->icon('Edit')
                                  ->url(function ($model) {
                                      return "items/{$model->id}/edit";
                                  });

        $deleteAction = ColumnAction::make('delete')
                                    ->label('Delete')
                                    ->icon('Trash2')
                                    ->url(function ($model) {
                                        return "items/{$model->id}/delete";
                                    });

        $viewAction = ColumnAction::make('view')
                                  ->label('View')
                                  ->icon('Eye');  // No URL callback for this action

        // Create action group
        $actionGroup = ColumnActionGroup::make()
                                        ->label('Actions')
                                        ->icon('Ellipsis', 'right')
                                        ->props(['variant' => 'outline'])
                                        ->actions([$editAction, $deleteAction, $viewAction]);

        // Create table with action column
        $table = EloquentTable::make(TestModel::query())->columns([
            ActionColumn::make('actions')
                        ->label('Actions')
                        ->action($actionGroup),
            Column::make('name')
        ]);

        $datatable->table($table);

        // Get data
        request()->replace(['pageSize' => 10]);
        $data = $datatable->getData();

        // Check that we have the expected number of items
        $this->assertGreaterThanOrEqual(3, $data->total());

        // Check the first item (Alice)
        $alice = $data->getCollection()->firstWhere('name', 'Alice');
        $this->assertNotNull($alice);

        // Check that the action column is correctly processed
        $this->assertArrayHasKey('actions_action', $alice);
        $actionData = $alice['actions_action'];

        // Verify the action group properties
        $this->assertEquals('Actions', $actionData['label']);
        $this->assertEquals('Ellipsis', $actionData['icon']);
        $this->assertEquals('right', $actionData['iconPosition']);
        $this->assertEquals(['variant' => 'outline'], $actionData['props']);

        // Check that the actions array has 3 items
        $this->assertCount(3, $actionData['actions']);

        // Check that the edit action has a URL
        $this->assertEquals('Edit', $actionData['actions'][0]['label']);
        $this->assertStringStartsWith("items/", $actionData['actions'][0]['url']);
        $this->assertStringEndsWith("/edit", $actionData['actions'][0]['url']);

        // Check that the delete action has a URL
        $this->assertEquals('Delete', $actionData['actions'][1]['label']);
        $this->assertStringStartsWith("items/", $actionData['actions'][1]['url']);
        $this->assertStringEndsWith("/delete", $actionData['actions'][1]['url']);

        // Check that the view action doesn't have a URL
        $this->assertEquals('View', $actionData['actions'][2]['label']);
        $this->assertArrayNotHasKey('url', $actionData['actions'][2]);
    }

    public function test_get_data_with_non_group_action_column()
    {
        $datatable = new TestModelDataTable();

        // Create a simple action instead of a ColumnActionGroup
        $singleAction = ColumnAction::make('edit')
                                    ->label('Edit')
                                    ->icon('EditIcon')
                                    ->url(function ($model) {
                                        return "items/{$model->id}/edit";
                                    });

        // Create table with action column that has a non-group action
        $table = EloquentTable::make(TestModel::query())->columns([
            ActionColumn::make('actions')
                        ->label('Actions')
                        ->action($singleAction),
            Column::make('name')
        ]);

        $datatable->table($table);

        // Get data
        request()->replace(['pageSize' => 10]);
        $data = $datatable->getData();

        // Check that we have the expected number of items
        $this->assertGreaterThanOrEqual(3, $data->total());

        // Check the first item (Alice)
        $alice = $data->getCollection()->firstWhere('name', 'Alice');
        $this->assertNotNull($alice);

        // Check that the action column is correctly processed
        $this->assertArrayHasKey('actions_action', $alice);
    }

    public function test_clear_filter()
    {
        $datatable = new TestModelDataTable();
        $table     = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ])->filters([
            Filter::make('status')
                  ->options(['active', 'inactive'])
                  ->query(fn($query, $value) => $query->where('status', $value))
        ]);
        $datatable->table($table);

        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('status', 'active'));
        $this->assertTrue($results->contains('status', 'inactive'));


        request()->replace(['filters' => ['status' => 'active']]);
        $results = $datatable->getResults()->get();
        $this->assertTrue($results->doesntContain('status', 'inactive'));

        request()->replace(['filters' => []]);

        $results = $datatable->getResults()->get();
        $this->assertTrue($results->contains('status', 'active'));
        $this->assertTrue($results->contains('status', 'inactive'));


    }

    public function test_handle_confirmation_with_single_column_action()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 456, 'name' => 'Test Model']);

        // Create a column action with confirm callback
        $columnAction = ColumnAction::make('single_action')
                                    ->confirm(function ($model) {
                                        return [
                                            'title'   => 'Confirm Single Action',
                                            'message' => "Are you sure you want to perform this action on {$model->name}?",
                                            'confirm' => 'Yes',
                                            'cancel'  => 'No'
                                        ];
                                    });

        // Create an action column with the single action (not a group)
        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnAction);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        // Create a request with the action name ending with _confirm
        $request = new Request([
            'action' => 'single_action_confirm',
            'ids'    => [456]
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction which will internally call handleConfirmation
        $result = $datatable->handleAction();

        $this->assertEquals([
            'confirmData' => [
                'title'   => 'Confirm Single Action',
                'message' => 'Are you sure you want to perform this action on Test Model?',
                'confirm' => 'Yes',
                'cancel'  => 'No'
            ]
        ], $result);
    }

    public function test_handle_action_with_non_existent_model()
    {
        $datatable = new TestModelDataTable();

        // Create a column action
        $columnAction = ColumnAction::make('test_action')
                                    ->handle(function ($model) {
                                        return "Handled action for {$model->name}";
                                    });

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnAction);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        // Create a request with a non-existent model ID
        $request = new Request([
            'action' => 'test_action',
            'ids'    => [99999] // Non-existent ID
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction
        $result = $datatable->handleAction();

        // Should return null because the model doesn't exist
        $this->assertNull($result);
    }

    public function test_handle_action_with_non_action_column()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 789, 'name' => 'Test Model']);

        // Create a regular column (not an ActionColumn)
        $regularColumn = Column::make('name');

        $table = EloquentTable::make(TestModel::query())->columns([$regularColumn]);
        $datatable->table($table);

        // Create a request
        $request = new Request([
            'action' => 'some_action',
            'ids'    => [789]
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction
        $result = $datatable->handleAction();

        // Should return null because there are no action columns
        $this->assertNull($result);
    }

    public function test_handle_action_with_no_matching_column_action()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 789, 'name' => 'Test Model']);

        // Create a column action with a different name
        $columnAction = ColumnAction::make('different_action')
                                    ->handle(function ($model) {
                                        return "Handled action for {$model->name}";
                                    });

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnAction);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        // Create a request with a non-matching action name
        $request = new Request([
            'action' => 'non_matching_action',
            'ids'    => [789]
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction
        $result = $datatable->handleAction();

        // Should return null because there's no matching action
        $this->assertNull($result);
    }

    public function test_handle_confirmation_with_non_existent_model()
    {
        $datatable = new TestModelDataTable();

        // Create a column action with confirm callback
        $columnAction = ColumnAction::make('test_action')
                                    ->confirm(function ($model) {
                                        return [
                                            'title'   => 'Confirm Action',
                                            'message' => "Are you sure?",
                                            'confirm' => 'Yes',
                                            'cancel'  => 'No'
                                        ];
                                    });

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnAction);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        // Create a request with a non-existent model ID
        $request = new Request([
            'action' => 'test_action_confirm',
            'ids'    => [99999] // Non-existent ID
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction which will internally call handleConfirmation
        $result = $datatable->handleAction();

        // Should return null because the model doesn't exist
        $this->assertNull($result);
    }

    public function test_handle_confirmation_with_non_action_column()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 789, 'name' => 'Test Model']);

        // Create a regular column (not an ActionColumn)
        $regularColumn = Column::make('name');

        $table = EloquentTable::make(TestModel::query())->columns([$regularColumn]);
        $datatable->table($table);

        // Create a request
        $request = new Request([
            'action' => 'some_action_confirm',
            'ids'    => [789]
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction which will internally call handleConfirmation
        $result = $datatable->handleAction();

        // Should return null because there are no action columns
        $this->assertNull($result);
    }

    public function test_handle_confirmation_with_no_matching_column_action()
    {
        $datatable = new TestModelDataTable();

        // Create a test model
        $model = TestModel::factory()->create(['id' => 789, 'name' => 'Test Model']);

        // Create a column action with a different name
        $columnAction = ColumnAction::make('different_action')
                                    ->confirm(function ($model) {
                                        return [
                                            'title'   => 'Confirm Action',
                                            'message' => "Are you sure?",
                                            'confirm' => 'Yes',
                                            'cancel'  => 'No'
                                        ];
                                    });

        $actionColumn = ActionColumn::make('actions')
                                    ->action($columnAction);

        $table = EloquentTable::make(TestModel::query())->columns([$actionColumn]);
        $datatable->table($table);

        // Create a request with a non-matching action name
        $request = new Request([
            'action' => 'non_matching_action_confirm',
            'ids'    => [789]
        ]);

        $this->app->instance(Request::class, $request);

        // Call handleAction which will internally call handleConfirmation
        $result = $datatable->handleAction();

        // Should return null because there's no matching action
        $this->assertNull($result);
    }
}
