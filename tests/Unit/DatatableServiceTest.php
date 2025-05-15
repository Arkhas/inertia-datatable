<?php
namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Services\DatatableService;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class DatatableServiceTest extends TestCase
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

    public function test_sort_calls_order_by()
    {
        $query = TestModel::query();
        $service = new DatatableService($query);
        $service->sort('name', 'desc');

        $results = $query->get();
        $this->assertEquals(['Charlie', 'Bob', 'Alice'], $results->pluck('name')->toArray());
    }

    public function test_search_calls_or_where_on_fillable()
    {
        $query = TestModel::query();
        $service = new DatatableService($query);
        $service->search('Alice');

        $results = $query->get();
        $this->assertEquals(['Alice'], $results->pluck('name')->toArray());
    }

    public function test_paginate_calls_skip_and_take()
    {
        $query = TestModel::query();
        $service = new DatatableService($query);
        $service->paginate(2, 1);

        $results = $query->get();
        $this->assertEquals(['Alice', 'Bob'], $results->pluck('name')->toArray());
    }

    public function test_filter_calls_where()
    {
        $query = TestModel::query();
        $service = new DatatableService($query);
        $service->filter('status', 'active');

        $results = $query->get();
        $this->assertEquals(['Alice', 'Charlie'], $results->pluck('name')->toArray());
    }
}
