<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Actions\TableAction;

class TableActionTest extends TestCase
{
    public function test_make_and_get_name()
    {
        $action = TableAction::make('foo');
        $this->assertEquals('foo', $action->getName());
    }

    public function test_label_and_get_label()
    {
        $action = TableAction::make('foo')->label('Bar');
        $this->assertEquals('Bar', $action->getLabel());
    }

    public function test_get_label_fallback()
    {
        $action = TableAction::make('foo_bar');
        $this->assertEquals('Foo bar', $action->getLabel());
    }

    public function test_styles_and_get_styles()
    {
        $action = TableAction::make('foo')->styles('primary');
        $this->assertEquals('primary', $action->getStyles());
    }

    public function test_icon_and_get_icon()
    {
        $action = TableAction::make('foo')->icon('edit');
        $this->assertEquals('edit', $action->getIcon());
    }

    public function test_props_and_get_props()
    {
        $props = ['confirm' => true, 'message' => 'Are you sure?'];
        $action = TableAction::make('foo')->props($props);
        $this->assertEquals($props, $action->getProps());
    }

    public function test_handle_and_get_handle_callback()
    {
        $callback = function ($ids) {
            return count($ids);
        };
        $action = TableAction::make('foo')->handle($callback);
        $this->assertIsCallable($action->getHandleCallback());
    }

    public function test_execute_with_callback()
    {
        $callback = function ($ids) {
            return count($ids);
        };
        $action = TableAction::make('foo')->handle($callback);
        $this->assertEquals(2, $action->execute([1, 2]));
    }

    public function test_execute_without_callback()
    {
        $action = TableAction::make('foo');
        $this->assertNull($action->execute([1, 2]));
    }

    public function test_to_array()
    {
        $action = TableAction::make('edit')
            ->label('Edit')
            ->icon('edit')
            ->props(['confirm' => true]);

        $expected = [
            'type' => 'action',
            'name' => 'edit',
            'label' => 'Edit',
            'styles' => null,
            'icon' => 'edit',
            'iconPosition' => 'left',
            'props' => ['confirm' => true],
        ];

        $this->assertEquals($expected, $action->toArray());
    }
}
