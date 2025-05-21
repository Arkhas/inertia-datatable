<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Filters\FilterOption;
use Illuminate\Database\Eloquent\Builder;

class FilterOptionTest extends TestCase
{
    public function test_make_and_get_value_and_label()
    {
        $opt = FilterOption::make('foo');
        $this->assertEquals('foo', $opt->getValue());
        $this->assertEquals('Foo', $opt->getLabel());
        $opt->label('Bar');
        $this->assertEquals('Bar', $opt->getLabel());
    }

    public function test_icon_and_get_icon()
    {
        $opt = FilterOption::make('foo')->icon('icon-foo');
        $this->assertEquals('icon-foo', $opt->getIcon());
    }

    public function test_query_callback_and_get_query_callback()
    {
        $opt = FilterOption::make('foo')->query(function () {});
        $this->assertIsCallable($opt->getQueryCallback());
    }

    public function test_apply_query_with_callback()
    {
        $opt = FilterOption::make('foo')->query(function ($query, $keyword) {
            $query->where('foo', $keyword);
        });
        $query = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('orWhere')->once()->withArgs(function ($closure) {
            $subQuery = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
            $subQuery->shouldReceive('where')->once()->with('foo', 'bar');
            $closure($subQuery);
            return true;
        });
        $opt->applyQuery($query, 'bar');
    }

    public function test_apply_query_without_callback()
    {
        $opt = FilterOption::make('foo');
        $query = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('orWhere')->once()->withArgs(function ($closure) {
            $subQuery = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
            $subQuery->shouldReceive('where')->once()->with('foo', 'like', '%bar%');
            $closure($subQuery);
            return true;
        });
        $opt->applyQuery($query, 'bar');
    }

    public function test_apply_query_with_fallback_like()
    {
        $opt = FilterOption::make('foo');
        $query = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('orWhere')->once()->withArgs(function ($closure) {
            $subQuery = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
            $subQuery->shouldReceive('where')->once()->with('foo', 'like', '%bar%');
            $closure($subQuery);
            return true;
        });
        $opt->applyQuery($query, 'bar');
    }

    public function test_apply_query_with_null_keyword()
    {
        $opt = FilterOption::make('foo');
        $query = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('orWhere')->once()->withArgs(function ($closure) {
            $subQuery = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
            $subQuery->shouldReceive('where')->once()->with('foo', 'like', '%%');
            $closure($subQuery);
            return true;
        });
        $opt->applyQuery($query, null);
    }

    public function test_count_and_get_count()
    {
        // Test with a direct count value
        $opt = FilterOption::make('foo')->count(function() { return 42; });
        $this->assertEquals(42, $opt->getCount());

        // Test that count is included in toArray
        $array = $opt->toArray();
        $this->assertArrayHasKey('count', $array);
        $this->assertEquals(42, $array['count']);
    }
}
