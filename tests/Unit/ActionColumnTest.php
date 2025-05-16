<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Columns\ActionColumn;
use Arkhas\InertiaDatatable\Columns\ColumnActionGroup;
use Arkhas\InertiaDatatable\Columns\ColumnAction;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class ActionColumnTest extends TestCase
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
        $column = ActionColumn::make();
        $this->assertEquals('actions', $column->getName());

        $column = ActionColumn::make('custom_actions');
        $this->assertEquals('custom_actions', $column->getName());
    }

    public function test_action_and_get_action()
    {
        $actionGroup = ColumnActionGroup::make()
            ->icon('Edit')
            ->actions([
                ColumnAction::make('edit')
                    ->label('Edit')
                    ->icon('Edit')
            ]);

        $column = ActionColumn::make()->action($actionGroup);
        $this->assertEquals($actionGroup, $column->getAction());
    }

    public function test_render_html_returns_null()
    {
        $column = ActionColumn::make();
        $model = (object)['id' => 1];
        $this->assertNull($column->renderHtml($model));
    }

    public function test_is_not_sortable_by_default()
    {
        $column = ActionColumn::make();
        $this->assertFalse($column->isSortable());
    }

    public function test_is_not_searchable_by_default()
    {
        $column = ActionColumn::make();
        $this->assertFalse($column->isSearchable());
    }

    public function test_label_and_get_label()
    {
        $column = ActionColumn::make()->label('Actions');
        $this->assertEquals('Actions', $column->getLabel());
    }
}