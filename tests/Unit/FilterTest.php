<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Filters\Filter;
use Arkhas\InertiaDatatable\Filters\FilterOption;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class FilterTest extends TestCase
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

    public function test_make_and_get_name_and_label()
    {
        $filter = Filter::make('status');
        $this->assertEquals('status', $filter->getName());
        $this->assertEquals('Status', $filter->getLabel());
        $filter->label('Statut');
        $this->assertEquals('Statut', $filter->getLabel());
    }

    public function test_options_and_get_options()
    {
        $filter = Filter::make('type')->options(['a' => 'A', 'b' => 'B']);
        $this->assertEquals(['a' => 'A', 'b' => 'B'], $filter->getOptions());
    }

    public function test_options_with_filter_options_and_icons()
    {
        $opt1 = FilterOption::make('foo')->label('Foo')->icon('icon-foo');
        $opt2 = FilterOption::make('bar')->label('Bar')->icon('icon-bar');
        $filter = Filter::make('type')->options([$opt1, $opt2]);
        $this->assertEquals(['foo' => 'Foo', 'bar' => 'Bar'], $filter->getOptions());
        $this->assertEquals(['foo' => 'icon-foo', 'bar' => 'icon-bar'], $filter->getIcons());
        $this->assertCount(2, $filter->getFilterOptions());
    }

    public function test_multiple_and_is_multiple()
    {
        $filter = Filter::make('type')->multiple();
        $this->assertTrue($filter->isMultiple());
        $filter->multiple(false);
        $this->assertFalse($filter->isMultiple());
    }

    public function test_query_callback_and_apply_filter()
    {
        $filter = Filter::make('type')->query(function ($query, $values) {
            $query->where('type', $values[0]);
        });
        $this->assertIsCallable($filter->getQueryCallback());
        $query = TestModel::query();
        $filter->applyFilter($query, 'active');
        $this->assertStringContainsString('where "type" = ?', $query->toSql());
    }

    public function test_apply_filter_with_filter_options_and_query_callback()
    {
        $opt = FilterOption::make('foo')->label('Foo')->query(function ($query, $value) {
            $query->where('foo', $value);
        });
        $filter = Filter::make('type')->options([$opt]);
        $query = TestModel::query();
        $filter->applyFilter($query, 'foo');
        $this->assertStringContainsString('where (("foo" = ?))', $query->toSql());
    }

    public function test_apply_filter_fallback_to_where_in()
    {
        $filter = Filter::make('type');
        $query = TestModel::query();
        $filter->applyFilter($query, ['a', 'b']);
        $this->assertStringContainsString('where "type" in (?, ?)', $query->toSql());
    }

    public function test_icons_and_get_icons()
    {
        $filter = Filter::make('type')->icons(['foo' => 'icon-foo', 'bar' => 'icon-bar']);
        $this->assertEquals(['foo' => 'icon-foo', 'bar' => 'icon-bar'], $filter->getIcons());
    }

    public function test_apply_filter_with_query_callback_only()
    {
        $filter = Filter::make('type')->query(function ($query, $values) {
            $query->where('type', $values[0]);
        });
        $query = TestModel::query();
        $filter->applyFilter($query, 'foo');
        $this->assertStringContainsString('where "type" = ?', $query->toSql());
    }

    public function test_apply_filter_with_filter_options_only()
    {
        $opt = FilterOption::make('foo')->label('Foo')->query(function ($query, $value) {
            $query->where('foo', $value);
        });
        $filter = Filter::make('type')->options([$opt]);
        $query = TestModel::query();
        $filter->applyFilter($query, 'foo');
        $this->assertStringContainsString('where (("foo" = ?))', $query->toSql());
    }

    public function test_apply_filter_with_empty_values()
    {
        $filter = Filter::make('type');
        $query = TestModel::query();
        $filter->applyFilter($query, '');
        $filter->applyFilter($query, []);
        $this->assertEquals('select * from "test_models"', $query->toSql());
    }

    public function test_apply_filter_with_comma_separated_string()
    {
        $filter = Filter::make('type');
        $query = TestModel::query();
        $filter->applyFilter($query, 'a,b');
        $this->assertStringContainsString('where "type" in (?, ?)', $query->toSql());
    }

    public function test_to_array()
    {
        $filter = Filter::make('status')
            ->label('Status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->icons(['active' => 'check', 'inactive' => 'x'])
            ->multiple(true);

        $expected = [
            'name' => 'status',
            'label' => 'Status',
            'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
            'icons' => ['active' => 'check', 'inactive' => 'x'],
            'iconPositions' => [],
            'multiple' => true
        ];

        $this->assertEquals($expected, $filter->toArray());
    }

    public function test_to_array_with_icon_positions()
    {
        $filter = Filter::make('status')
            ->label('Status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->icons(['active' => 'check', 'inactive' => 'x'])
            ->iconPositions(['active' => 'right', 'inactive' => 'left'])
            ->multiple(false);

        $expected = [
            'name' => 'status',
            'label' => 'Status',
            'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
            'icons' => ['active' => 'check', 'inactive' => 'x'],
            'iconPositions' => ['active' => 'right', 'inactive' => 'left'],
            'multiple' => false
        ];

        $this->assertEquals($expected, $filter->toArray());
    }
}
