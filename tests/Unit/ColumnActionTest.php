<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Columns\ColumnAction;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class ColumnActionTest extends TestCase
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
        $action = ColumnAction::make('edit');
        $this->assertEquals('edit', $action->getName());
    }

    public function test_label_and_get_label()
    {
        $action = ColumnAction::make('edit')->label('Edit Item');
        $this->assertEquals('Edit Item', $action->getLabel());

        // Test default label (capitalized name)
        $action = ColumnAction::make('edit');
        $this->assertEquals('Edit', $action->getLabel());

        // Test with underscores
        $action = ColumnAction::make('edit_item');
        $this->assertEquals('Edit item', $action->getLabel());
    }

    public function test_icon_and_get_icon()
    {
        $action = ColumnAction::make('edit')->icon('Edit');
        $this->assertEquals('Edit', $action->getIcon());
    }

    public function test_icon_position_and_get_icon_position()
    {
        $action = ColumnAction::make('edit')->icon('Edit', 'right');
        $this->assertEquals('right', $action->getIconPosition());

        // Test default position
        $action = ColumnAction::make('edit')->icon('Edit');
        $this->assertEquals('left', $action->getIconPosition());
    }

    public function test_props_and_get_props()
    {
        $props = ['variant' => 'destructive', 'size' => 'sm'];
        $action = ColumnAction::make('delete')->props($props);
        $this->assertEquals($props, $action->getProps());
    }

    public function test_url_and_get_url_callback()
    {
        $callback = function($model) {
            return "tasks/{$model->id}/edit";
        };
        $action = ColumnAction::make('edit')->url($callback);
        $this->assertSame($callback, $action->getUrlCallback());
        $this->assertTrue($action->hasUrlCallback());

        $model = (object)['id' => 123];
        $this->assertEquals('tasks/123/edit', $action->executeUrlCallback($model));
    }

    public function test_execute_url_callback_without_callback()
    {
        $action = ColumnAction::make('view'); // No URL callback
        $this->assertFalse($action->hasUrlCallback());

        $model = (object)['id' => 123];
        $this->assertNull($action->executeUrlCallback($model));
    }

    public function test_separator_and_has_separator()
    {
        $action = ColumnAction::make('edit')->separator();
        $this->assertTrue($action->hasSeparator());

        $action = ColumnAction::make('edit')->separator(false);
        $this->assertFalse($action->hasSeparator());
    }

    public function test_handle_and_get_handle_callback()
    {
        $callback = function ($ids) {
            return count($ids);
        };

        $action = ColumnAction::make('delete')->handle($callback);
        $this->assertIsCallable($action->getHandleCallback());
    }

    public function test_execute()
    {
         $model = TestModel::factory()->create(['id' => 123, 'name' => 'Test Item']);

        $action = ColumnAction::make('delete')->handle(function (TestModel $model) {
            return $model->id;
        });

        $this->assertEquals(123, $action->execute($model));
    }

    public function test_execute_returns_null_without_callback()
    {
        $model = TestModel::factory()->create(['id' => 123, 'name' => 'Test Item']);
        $action = ColumnAction::make('view');
        $this->assertNull($action->execute($model));
    }

    public function test_to_array_with_model_and_confirm_callback()
    {
        $model = TestModel::factory()->create(['id' => 123, 'name' => 'Test Item']);

        $action = ColumnAction::make('delete')
            ->label('Delete')
            ->confirm(function ($model) {
                return [
                    'title' => 'Confirm Delete',
                    'message' => "Are you sure you want to delete {$model->name}?",
                    'confirm' => 'Yes',
                    'cancel' => 'No'
                ];
            });

        $result = $action->toArray($model);

        $this->assertTrue($result['hasConfirmCallback']);
        $this->assertArrayNotHasKey('confirmData', $result);
    }

    public function test_get_confirm_data_without_callback()
    {
        $action = ColumnAction::make('edit');
        $model = TestModel::factory()->create();
        $this->assertNull($action->getConfirmData($model));
    }

    public function test_get_confirm_data_with_callback()
    {
        $model = TestModel::factory()->create(['name' => 'Test Item']);

        $action = ColumnAction::make('delete')
            ->confirm(function ($model) {
                return [
                    'title' => 'Confirm Delete',
                    'message' => "Are you sure you want to delete {$model->name}?",
                    'confirm' => 'Yes',
                    'cancel' => 'No'
                ];
            });

        $result = $action->getConfirmData($model);

        $this->assertEquals('Confirm Delete', $result['title']);
        $this->assertEquals('Are you sure you want to delete Test Item?', $result['message']);
    }
}
