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

    public function test_action_group_and_get_action_group()
    {
        $action =
                ColumnAction::make('edit')
                    ->label('Edit')
                    ->icon('Edit');

        $column = ActionColumn::make()->action($action);
        $this->assertEquals($action, $column->getAction());
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

    public function test_has_confirm_callback_with_column_action()
    {
        // Test with a ColumnAction that has a confirmation callback
        $action = ColumnAction::make('delete')
            ->confirm(function ($model) {
                return [
                    'title' => 'Confirm Delete',
                    'message' => 'Are you sure?',
                    'confirm' => 'Yes',
                    'cancel' => 'No'
                ];
            });

        $column = ActionColumn::make()->action($action);
        $this->assertTrue($column->hasConfirmCallback());

        // Test with a ColumnAction that doesn't have a confirmation callback
        $action = ColumnAction::make('edit');
        $column = ActionColumn::make()->action($action);
        $this->assertFalse($column->hasConfirmCallback());
    }

    public function test_has_confirm_callback_with_column_action_group()
    {
        // Test with a ColumnActionGroup that has at least one action with a confirmation callback
        $actionGroup = ColumnActionGroup::make()
            ->icon('Edit')
            ->actions([
                ColumnAction::make('edit')
                    ->label('Edit')
                    ->icon('Edit'),
                ColumnAction::make('delete')
                    ->label('Delete')
                    ->icon('Trash')
                    ->confirm(function ($model) {
                        return [
                            'title' => 'Confirm Delete',
                            'message' => 'Are you sure?',
                            'confirm' => 'Yes',
                            'cancel' => 'No'
                        ];
                    })
            ]);

        $column = ActionColumn::make()->action($actionGroup);
        $this->assertTrue($column->hasConfirmCallback());

        // Test with a ColumnActionGroup that doesn't have any actions with a confirmation callback
        $actionGroup = ColumnActionGroup::make()
            ->icon('Edit')
            ->actions([
                ColumnAction::make('edit')
                    ->label('Edit')
                    ->icon('Edit'),
                ColumnAction::make('view')
                    ->label('View')
                    ->icon('Eye')
            ]);

        $column = ActionColumn::make()->action($actionGroup);
        $this->assertFalse($column->hasConfirmCallback());
    }
}
