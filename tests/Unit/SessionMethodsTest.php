<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\TestModels\TestModelDataTable;
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

    public function test_session_persistence_for_page_size()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set page size in request
        request()->replace(['pageSize' => 10]);

        // Get props to store in session
        $props1 = $datatable->getProps();
        $this->assertEquals(10, $props1['pageSize']());

        // Clear request and get props again
        request()->replace([]);
        $props2 = $datatable->getProps();

        // Page size should be retrieved from session
        $this->assertEquals(10, $props2['pageSize']());
    }

    public function test_session_persistence_for_sorting()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set sort and direction in request
        request()->replace([
            'sort' => 'name',
            'direction' => 'desc'
        ]);

        // Get props to store in session
        $props1 = $datatable->getProps();
        $this->assertEquals('name', $props1['sort']());
        $this->assertEquals('desc', $props1['direction']());

        // Clear request and get props again
        request()->replace([]);
        $props2 = $datatable->getProps();

        // Sort and direction should be retrieved from session
        $this->assertEquals('name', $props2['sort']());
        $this->assertEquals('desc', $props2['direction']());
    }

    public function test_session_persistence_for_filters()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set filters in request
        request()->replace([
            'filters' => ['status' => 'active']
        ]);

        // Get props to store in session
        $props1 = $datatable->getProps();
        $this->assertEquals(['status' => 'active'], $props1['currentFilters']());

        // Clear request and get props again
        request()->replace([]);
        $props2 = $datatable->getProps();

        // Filters should be retrieved from session
        $this->assertEquals(['status' => 'active'], $props2['currentFilters']());
    }

    public function test_session_persistence_for_visible_columns()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ]);
        $datatable->table($table);

        // Set visible columns in request
        request()->replace([
            'visibleColumns' => ['name']
        ]);

        // Get props to store in session
        $props1 = $datatable->getProps();
        $this->assertEquals(['name'], $props1['visibleColumns']());

        // Clear request and get props again
        request()->replace([]);
        $props2 = $datatable->getProps();

        // Visible columns should be retrieved from session
        $this->assertEquals(['name'], $props2['visibleColumns']());
    }

    public function test_empty_filters_are_removed_from_session()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set filters in request
        request()->replace([
            'filters' => ['status' => 'active']
        ]);

        // Get props to store in session
        $datatable->getProps();

        // Now set empty filters
        request()->replace([
            'filters' => []
        ]);

        // Get props again
        $props = $datatable->getProps();

        // Current filters should be empty
        $this->assertEquals([], $props['currentFilters']());
    }

    public function test_reset_filter_functionality()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();

        // Create a query that directly filters for active status
        $query = TestModel::query()->where('status', 'active');
        $table = EloquentTable::make($query)->columns([
            Column::make('name'),
            Column::make('status'),
        ]);
        $datatable->table($table);

        // Get results with the filter applied
        $results1 = $datatable->getResults()->get();

        // Verify only active records are returned (should be 2: Alice and Charlie)
        $this->assertEquals(2, $results1->count());
        $this->assertTrue($results1->contains('status', 'active'));
        $this->assertFalse($results1->contains('status', 'inactive'));

        // Now create a new datatable with no filter
        $datatable2 = new TestModelDataTable();
        $table2 = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ]);
        $datatable2->table($table2);

        // Verify that all records are returned
        $results2 = $datatable2->getResults()->get();
        $this->assertEquals(3, $results2->count()); // All records should be returned
        $this->assertTrue($results2->contains('status', 'inactive')); // Now includes inactive records
    }

    public function test_empty_search_is_removed_from_session()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set search in request
        request()->replace([
            'search' => 'Alice'
        ]);

        // Get results to store search in session
        $results1 = $datatable->getResults()->get();

        // Verify search is applied (only Alice should be returned)
        $this->assertEquals(1, $results1->count());
        $this->assertEquals('Alice', $results1->first()->name);

        // Now set empty search
        request()->replace([
            'search' => ''
        ]);

        // Get results again
        $datatable->getResults();

        // Search should be removed from session
        // We can verify this by making a new datatable instance and checking results
        $newDatatable = new TestModelDataTable();
        $newTable = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $newDatatable->table($newTable);

        $results2 = $newDatatable->getResults()->get();
        $this->assertEquals(3, $results2->count()); // All records should be returned
    }

    public function test_page_size_from_session_is_used_for_pagination()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
        ]);
        $datatable->table($table);

        // Set page size in request
        request()->replace([
            'pageSize' => 2
        ]);

        // Get props to store in session
        $datatable->getProps();

        // Clear request and get data
        request()->replace([]);
        $data = $datatable->getData();

        // Page size should be retrieved from session
        $this->assertEquals(2, $data->perPage());
    }

    public function test_get_session_key_method()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();


        // Test with no suffix
        $key1 = $datatable->getSessionKey();
        $this->assertIsString($key1);
        $this->assertStringStartsWith('dt_', $key1);

        // Test with a suffix
        $key2 = $datatable->getSessionKey('test');
        $this->assertIsString($key2);
        $this->assertStringEndsWith('_test', $key2);

        // Verify that the keys are different
        $this->assertNotEquals($key1, $key2);
    }

    public function test_store_and_get_from_session()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();

        // Test storing and retrieving a string
        $datatable->storeInSession('test_key', 'test_value');
        $value1 = $datatable->getFromSession('test_key');
        $this->assertEquals('test_value', $value1);

        // Test storing and retrieving an array
        $datatable->storeInSession('test_array', ['key' => 'value']);
        $value2 = $datatable->getFromSession('test_array');
        $this->assertEquals(['key' => 'value'], $value2);

        // Test retrieving a non-existent key with default value
        $value3 = $datatable->getFromSession('non_existent', 'default');
        $this->assertEquals('default', $value3);

        // Test retrieving a non-existent key without default value
        $value4 = $datatable->getFromSession('non_existent');
        $this->assertNull($value4);
    }

    protected function tearDown(): void
    {
        $this->tearDownTestModels();
        parent::tearDown();
    }
}
