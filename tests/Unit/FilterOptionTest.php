<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Filters\FilterOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestModels\TestModel;

class FilterOptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_models');
        parent::tearDown();
    }

    public function test_make_and_get_value_and_label()
    {
        $opt = FilterOption::make('name');
        $this->assertEquals('name', $opt->getValue());
        $this->assertEquals('Name', $opt->getLabel());
        $opt->label('Bar');
        $this->assertEquals('Bar', $opt->getLabel());
    }

    public function test_icon_and_get_icon()
    {
        $opt = FilterOption::make('name')->icon('icon-name');
        $this->assertEquals('icon-name', $opt->getIcon());
    }

    public function test_query_callback_and_get_query_callback()
    {
        $opt = FilterOption::make('name')->query(function () {});
        $this->assertIsCallable($opt->getQueryCallback());
    }

    public function test_apply_query_with_callback()
    {
        $opt = FilterOption::make('name')->query(function ($query, $keyword) {
            $query->where('name', $keyword);
        });

        $query = TestModel::query();

        // Apply the filter
        $opt->applyQuery($query, 'bar');

        // Convert to SQL to verify the query structure
        $sql = $query->toSql();
        $this->assertStringContainsString('"name" = ?', $sql);
        $this->assertEquals(['bar'], $query->getBindings());
    }

    public function test_apply_query_without_callback()
    {
        $opt = FilterOption::make('name');

        $query = TestModel::query();

        // Apply the filter
        $opt->applyQuery($query, 'bar');

        // Convert to SQL to verify the query structure
        $sql = $query->toSql();
        $this->assertStringContainsString('"name" like ?', $sql);
        $this->assertEquals(['%bar%'], $query->getBindings());
    }

    public function test_apply_query_with_fallback_like()
    {
        $opt = FilterOption::make('name');

        $query = TestModel::query();

        // Apply the filter
        $opt->applyQuery($query, 'bar');

        // Convert to SQL to verify the query structure
        $sql = $query->toSql();
        $this->assertStringContainsString('"name" like ?', $sql);
        $this->assertEquals(['%bar%'], $query->getBindings());
    }

    public function test_apply_query_with_null_keyword()
    {
        $opt = FilterOption::make('name');

        $query = TestModel::query();

        // Apply the filter
        $opt->applyQuery($query, null);

        // Convert to SQL to verify the query structure
        $sql = $query->toSql();
        $this->assertStringContainsString('"name" like ?', $sql);
        $this->assertEquals(['%%'], $query->getBindings());
    }

    public function test_count_and_get_count()
    {
        // Test with a direct count value
        $opt = FilterOption::make('name')->count(function() { return 42; });
        $this->assertEquals(42, $opt->getCount());

        // Test that count is included in toArray
        $array = $opt->toArray();
        $this->assertArrayHasKey('count', $array);
        $this->assertEquals(42, $array['count']);
    }

    public function test_count_and_get_count_null()
    {
        // Test with a direct count value
        $opt = FilterOption::make('name');
        $this->assertEquals(null, $opt->getCount());

        // Test that count is included in toArray
        $array = $opt->toArray();
        $this->assertArrayHasKey('count', $array);
        $this->assertEquals(null, $array['count']);
    }
}
