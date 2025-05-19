<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\TestModels\SessionTestModelDataTable;
use Tests\TestModels\TestModel;
use Tests\TestModels\WithTestModels;
use Arkhas\InertiaDatatable\EloquentTable;
use Arkhas\InertiaDatatable\Columns\Column;
use Illuminate\Support\Facades\Session;

class SessionMethodsTest extends TestCase
{
    use WithTestModels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestModels();
    }

    public function test_get_session_key_without_suffix()
    {
        $datatable = new SessionTestModelDataTable();
        $key = $datatable->getSessionKeyPublic();

        // The key should be a string with the format 'datatable_' + md5(class name)
        $this->assertIsString($key);
        $this->assertStringStartsWith('datatable_', $key);
        $this->assertEquals('datatable_' . md5(SessionTestModelDataTable::class), $key);
    }

    public function test_get_session_key_with_suffix()
    {
        $datatable = new SessionTestModelDataTable();
        $key = $datatable->getSessionKeyPublic('test_suffix');

        // The key should be a string with the format 'datatable_' + md5(class name) + '_' + suffix
        $this->assertIsString($key);
        $this->assertStringStartsWith('datatable_', $key);
        $this->assertEquals('datatable_' . md5(SessionTestModelDataTable::class) . '_test_suffix', $key);
    }

    public function test_store_and_get_from_session()
    {
        $datatable = new SessionTestModelDataTable();

        // Store a value in the session
        $datatable->storeInSessionPublic('test_key', 'test_value');

        // Get the value from the session
        $value = $datatable->getFromSessionPublic('test_key');

        // The value should be the same as what we stored
        $this->assertEquals('test_value', $value);
    }

    public function test_get_from_session_with_default()
    {
        $datatable = new SessionTestModelDataTable();

        // Get a value that doesn't exist in the session
        $value = $datatable->getFromSessionPublic('non_existent_key', 'default_value');

        // The value should be the default
        $this->assertEquals('default_value', $value);
    }

    public function test_get_props_stores_values_in_session()
    {
        $datatable = new SessionTestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set values in the request
        request()->replace([
            'pageSize' => 10,
            'sort' => 'name',
            'direction' => 'desc',
            'filters' => ['status' => 'active'],
            'visibleColumns' => ['name'],
        ]);

        // Call getProps to store values in session
        $datatable->getProps();

        // Check that values were stored in session
        $this->assertEquals(10, $datatable->getFromSessionPublic('pageSize'));
        $this->assertEquals('name', $datatable->getFromSessionPublic('sort'));
        $this->assertEquals('desc', $datatable->getFromSessionPublic('direction'));
        $this->assertEquals(['status' => 'active'], $datatable->getFromSessionPublic('filters'));
        $this->assertEquals(['name'], $datatable->getFromSessionPublic('visibleColumns'));
    }

    public function test_get_props_gets_values_from_session()
    {
        $datatable = new SessionTestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Store values in session
        $datatable->storeInSessionPublic('pageSize', 20);
        $datatable->storeInSessionPublic('sort', 'status');
        $datatable->storeInSessionPublic('direction', 'asc');
        $datatable->storeInSessionPublic('filters', ['name' => 'Alice']);
        $datatable->storeInSessionPublic('visibleColumns', ['status']);

        // Call getProps with empty request
        request()->replace([]);
        $props = $datatable->getProps();

        // Check that values were retrieved from session
        $this->assertEquals(20, $props['pageSize']());
        $this->assertEquals('status', $props['sort']());
        $this->assertEquals('asc', $props['direction']());
        $this->assertEquals(['name' => 'Alice'], $props['currentFilters']());
        $this->assertEquals(['status'], $props['visibleColumns']());
    }

    public function test_get_props_removes_empty_filters_from_session()
    {
        $datatable = new SessionTestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Store a filter in session
        $datatable->storeInSessionPublic('filters', ['status' => 'active']);

        // Call getProps with empty filters
        request()->replace(['filters' => []]);
        $datatable->getProps();

        // Check that filters were removed from session
        $this->assertNull($datatable->getFromSessionPublic('filters'));
    }

    public function test_get_results_removes_empty_filters_from_session()
    {
        $datatable = new SessionTestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Store a filter in session
        $datatable->storeInSessionPublic('filters', ['status' => 'active']);

        // Call getResults with empty filters
        request()->replace(['filters' => []]);
        $datatable->getResults();

        // Check that filters were removed from session
        $this->assertNull($datatable->getFromSessionPublic('filters'));
    }

    public function test_get_results_removes_empty_search_from_session()
    {
        $datatable = new SessionTestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Store a search term in session
        $datatable->storeInSessionPublic('search', 'Alice');

        // Call getResults with empty search
        request()->replace(['search' => '']);
        $datatable->getResults();

        // Check that search was removed from session
        $this->assertNull($datatable->getFromSessionPublic('search'));
    }

    public function test_get_data_gets_page_size_from_session()
    {
        $datatable = new SessionTestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Store page size in session
        $datatable->storeInSessionPublic('pageSize', 15);

        // Call getData with empty request
        request()->replace([]);
        $data = $datatable->getData();

        // Check that page size was retrieved from session
        $this->assertEquals(15, $data->perPage());
    }

    protected function tearDown(): void
    {
        $this->tearDownTestModels();
        parent::tearDown();
    }
}
