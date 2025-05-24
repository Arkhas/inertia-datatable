<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Columns\ColumnActionGroup;
use Arkhas\InertiaDatatable\Columns\ColumnAction;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class ColumnActionGroupTest extends TestCase
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

    public function test_make()
    {
        $group = ColumnActionGroup::make();
        $this->assertInstanceOf(ColumnActionGroup::class, $group);
    }

    public function test_label_and_get_label()
    {
        $group = ColumnActionGroup::make()->label('Actions');
        $this->assertEquals('Actions', $group->getLabel());

        // Test null label
        $group = ColumnActionGroup::make();
        $this->assertNull($group->getLabel());
    }

    public function test_icon_and_get_icon()
    {
        $group = ColumnActionGroup::make()->icon('Edit');
        $this->assertEquals('Edit', $group->getIcon());

        // Test default icon
        $group = ColumnActionGroup::make();
        $this->assertEquals('Ellipsis', $group->getIcon());
    }

    public function test_icon_position_and_get_icon_position()
    {
        $group = ColumnActionGroup::make()->icon('Edit', 'left');
        $this->assertEquals('left', $group->getIconPosition());

        // Test default position
        $group = ColumnActionGroup::make();
        $this->assertEquals('right', $group->getIconPosition());
    }

    public function test_props_and_get_props()
    {
        $props = ['variant' => 'outline', 'size' => 'sm'];
        $group = ColumnActionGroup::make()->props($props);
        $this->assertEquals($props, $group->getProps());

        // Test default props
        $group = ColumnActionGroup::make();
        $this->assertEquals([], $group->getProps());
    }

    public function test_actions_and_get_actions()
    {
        $actions = [
            ColumnAction::make('edit')->label('Edit'),
            ColumnAction::make('delete')->label('Delete')
        ];

        $group = ColumnActionGroup::make()->actions($actions);
        $this->assertEquals($actions, $group->getActions());

        // Test default actions
        $group = ColumnActionGroup::make();
        $this->assertEquals([], $group->getActions());
    }

    public function test_to_array_with_model()
    {
        // Create actions with URL callbacks
        $editAction = ColumnAction::make('edit')
            ->label('Edit')
            ->icon('Edit')
            ->url(function($model) {
                return "items/{$model->id}/edit";
            });

        $deleteAction = ColumnAction::make('delete')
            ->label('Delete')
            ->icon('Trash2')
            ->url(function($model) {
                return "items/{$model->id}/delete";
            });

        $viewAction = ColumnAction::make('view')
            ->label('View')
            ->icon('Eye');  // No URL callback for this action

        $group = ColumnActionGroup::make()
            ->label('Actions')
            ->icon('Ellipsis', 'right')
            ->props(['variant' => 'outline'])
            ->actions([$editAction, $deleteAction, $viewAction]);

        // Create a test model
        $model = TestModel::factory()->create(['id' => 123]);

        // Call toArrayWithModel with the test model
        $result = $group->toArray($model);

        // Verify the result
        $this->assertEquals('Actions', $result['label']);
        $this->assertEquals('Ellipsis', $result['icon']);
        $this->assertEquals('right', $result['iconPosition']);
        $this->assertEquals(['variant' => 'outline'], $result['props']);

        // Check that the actions array has 3 items
        $this->assertCount(3, $result['actions']);

        // Check that the edit action has a URL
        $this->assertEquals('Edit', $result['actions'][0]['label']);
        $this->assertEquals('items/123/edit', $result['actions'][0]['url']);

        // Check that the delete action has a URL
        $this->assertEquals('Delete', $result['actions'][1]['label']);
        $this->assertEquals('items/123/delete', $result['actions'][1]['url']);

        // Check that the view action doesn't have a URL
        $this->assertEquals('View', $result['actions'][2]['label']);
        $this->assertArrayNotHasKey('url', $result['actions'][2]);
    }
}
