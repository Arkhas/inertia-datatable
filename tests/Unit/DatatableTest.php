<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Services\DatatableService;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class DatatableTest extends TestCase
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

    public function test_can_sort_data_by_a_given_column_using_eloquent()
    {
        $query = TestModel::query();
        $datatable = new DatatableService($query);
        $datatable->sort('name', 'asc');

        $results = $query->get();

        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $results->pluck('name')->toArray());
    }

    public function test_can_search_data_by_a_keyword_using_eloquent()
    {
        $query = TestModel::query();
        $datatable = new DatatableService($query);
        $datatable->search('Alice');

        $results = $query->get();

        $this->assertEquals(['Alice'], $results->pluck('name')->toArray());
    }

    public function test_can_paginate_data_using_eloquent()
    {
        $query = TestModel::query();
        $datatable = new DatatableService($query);
        $datatable->paginate(2, 1);

        $results = $query->get();

        $this->assertEquals(['Alice', 'Bob'], $results->pluck('name')->toArray());
    }

    public function test_can_filter_data_by_a_specific_column_using_eloquent()
    {
        $query = TestModel::query();
        $datatable = new DatatableService($query);
        $datatable->filter('status', 'active');

        $results = $query->get();

        $this->assertEquals(['Alice', 'Charlie'], $results->pluck('name')->toArray());
    }
}
