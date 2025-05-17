<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Columns\Column;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class ColumnTest extends TestCase
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

    public function test_make_and_get_name()
    {
        $column = Column::make('foo');
        $this->assertEquals('foo', $column->getName());
    }

    public function test_order_callback_and_apply_order()
    {
        $column = Column::make('bar')->order(function ($query, $order) {
            $query->orderBy('bar', $order);
        });
        $this->assertIsCallable($column->getOrderCallback());
        $query = TestModel::query();
        $column->applyOrder($query, 'desc');
        $this->assertStringContainsString('order by "bar" desc', $query->toSql());
    }

    public function test_filter_callback_and_apply_filter()
    {
        $column = Column::make('baz')->filter(function ($query, $keywords) {
            $query->where('baz', $keywords[0]);
        });
        $this->assertIsCallable($column->getFilterCallback());
        $query = TestModel::query();
        $column->applyFilter($query, 'abc');
        $this->assertStringContainsString('where "baz" = ?', $query->toSql());
    }

    public function test_html_callback_and_render_html()
    {
        $column = Column::make('title')->html(fn($model) => strtoupper($model->title));
        $this->assertIsCallable($column->getHtmlCallback());
        $model = (object)['title' => 'foo'];
        $this->assertEquals('FOO', $column->renderHtml($model));
    }

    public function test_render_html_without_callback()
    {
        $column = Column::make('title');
        $model  = (object)['title' => 'bar'];
        $this->assertEquals('bar', $column->renderHtml($model));
    }

    public function test_icon_callback_and_render_icon()
    {
        $column = Column::make('icon')->icon(fn($model) => 'icon-' . $model->id);
        $this->assertIsCallable($column->getIconCallback());
        $model = (object)['id' => 42];
        $this->assertEquals('icon-42', $column->renderIcon($model));
    }

    public function test_render_icon_without_callback()
    {
        $column = Column::make('icon');
        $model  = (object)['id' => 1];
        $this->assertNull($column->renderIcon($model));
    }

    public function test_label_and_get_label()
    {
        $column = Column::make('foo')->label('Bar');
        $this->assertEquals('Bar', $column->getLabel());
    }

    public function test_sortable_and_is_sortable()
    {
        $column = Column::make('foo')->sortable(false);
        $this->assertFalse($column->isSortable());

        $column = Column::make('foo')->sortable(true);
        $this->assertTrue($column->isSortable());
    }

    public function test_searchable_and_is_searchable()
    {
        $column = Column::make('foo')->searchable(false);
        $this->assertFalse($column->isSearchable());

        $column = Column::make('foo')->searchable(true);
        $this->assertTrue($column->isSearchable());
    }


    public function test_apply_filter_when_not_searchable()
    {
        $column      = Column::make('foo')->searchable(false);
        $query       = TestModel::query();
        $originalSql = $query->toSql();

        $column->applyFilter($query, 'test');

        // SQL should not change since column is not searchable
        $this->assertEquals($originalSql, $query->toSql());
    }

    public function test_apply_order_when_not_sortable()
    {
        $column      = Column::make('foo')->sortable(false);
        $query       = TestModel::query();
        $originalSql = $query->toSql();

        $column->applyOrder($query, 'asc');

        // SQL should not change since column is not sortable
        $this->assertEquals($originalSql, $query->toSql());
    }

    public function test_to_array()
    {
        $column = Column::make('name')
                        ->label('Name')
                        ->icon(fn($model) => 'user')
                        ->sortable(false)
                        ->searchable(false)
                        ->toggable(false);

        $expected = [
            'name'         => 'name',
            'label'        => 'Name',
            'hasIcon'      => true,
            'sortable'     => false,
            'searchable'   => false,
            'toggable'     => false,
            'iconPosition' => 'left'
        ];

        $this->assertEquals($expected, $column->toArray());
    }

    public function test_to_array_with_custom_icon_position()
    {
        $column = Column::make('name')
                        ->label('Name')
                        ->icon(fn($model) => 'user', 'right');

        $expected = [
            'name'         => 'name',
            'label'        => 'Name',
            'hasIcon'      => true,
            'sortable'     => true,
            'searchable'   => true,
            'toggable'     => true,
            'iconPosition' => 'right'
        ];

        $this->assertEquals($expected, $column->toArray());
    }

    public function test_get_full_name_without_relation_path()
    {
        $column = Column::make('name');
        $this->assertEquals('name', $column->getFullName());
    }
}
