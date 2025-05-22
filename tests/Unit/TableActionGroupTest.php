<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Actions\TableAction;
use Arkhas\InertiaDatatable\Actions\TableActionGroup;
use Arkhas\InertiaDatatable\Columns\ColumnAction;
use Arkhas\InertiaDatatable\Columns\ColumnActionGroup;

class TableActionGroupTest extends TestCase
{
    public function test_make_and_get_name()
    {
        $group = TableActionGroup::make('foo');
        $this->assertEquals('foo', $group->getName());
    }

    public function test_label_and_get_label()
    {
        $group = TableActionGroup::make('foo')->label('Bar');
        $this->assertEquals('Bar', $group->getLabel());
    }

    public function test_get_label_fallback()
    {
        $group = TableActionGroup::make('foo_bar');
        $this->assertEquals('Foo bar', $group->getLabel());
    }

    public function test_styles_and_get_styles()
    {
        $group = TableActionGroup::make('foo')->styles('primary');
        $this->assertEquals('primary', $group->getStyles());
    }

    public function test_icon_and_get_icon()
    {
        $group = TableActionGroup::make('foo')->icon('edit');
        $this->assertEquals('edit', $group->getIcon());
    }

    public function test_props_and_get_props()
    {
        $props = ['confirm' => true, 'message' => 'Are you sure?'];
        $group = TableActionGroup::make('foo')->props($props);
        $this->assertEquals($props, $group->getProps());
    }

    public function test_actions_and_get_actions()
    {
        $action1 = TableAction::make('action1');
        $action2 = TableAction::make('action2');
        $group = TableActionGroup::make('foo')->actions([$action1, $action2]);
        $this->assertEquals([$action1, $action2], $group->getActions());
    }

    public function test_column_action_group_to_array()
    {
        $actions = [
            ColumnAction::make('edit')->label('Edit')->icon('Edit'),
            ColumnAction::make('delete')->label('Delete')->icon('Trash2')
        ];

        $group = ColumnActionGroup::make()
            ->label('Actions')
            ->icon('Ellipsis', 'right')
            ->props(['variant' => 'outline'])
            ->actions($actions);

        $expected = [
            'label' => 'Actions',
            'icon' => 'Ellipsis',
            'iconPosition' => 'right',
            'props' => ['variant' => 'outline'],
            'actions' => [
                [
                    'name' => 'edit',
                    'label' => 'Edit',
                    'icon' => 'Edit',
                    'iconPosition' => 'left',
                    'props' => [],
                    'hasUrlCallback' => false,
                    'separator' => false,
                    'hasConfirmCallback' => false,
                ],
                [
                    'name' => 'delete',
                    'label' => 'Delete',
                    'icon' => 'Trash2',
                    'iconPosition' => 'left',
                    'props' => [],
                    'hasUrlCallback' => false,
                    'separator' => false,
                    'hasConfirmCallback' => false,
                ]
            ],
        ];

        $this->assertEquals($expected, $group->toArray());
    }

    public function test_table_action_group_to_array()
    {
        $action1 = TableAction::make('edit')->label('Edit')->icon('edit');
        $action2 = TableAction::make('delete')->label('Delete')->icon('trash');

        $group = TableActionGroup::make('actions')
            ->label('Actions')
            ->styles('primary')
            ->icon('menu')
            ->props(['dropdown' => true])
            ->actions([$action1, $action2]);

        $expected = [
            'type' => 'group',
            'name' => 'actions',
            'label' => 'Actions',
            'styles' => 'primary',
            'icon' => 'menu',
            'iconPosition' => 'left',
            'props' => ['dropdown' => true],
            'actions' => [
                [
                    'type' => 'action',
                    'name' => 'edit',
                    'label' => 'Edit',
                    'styles' => null,
                    'icon' => 'edit',
                    'iconPosition' => 'left',
                    'props' => [],
                    'hasConfirmCallback' => false,
                ],
                [
                    'type' => 'action',
                    'name' => 'delete',
                    'label' => 'Delete',
                    'styles' => null,
                    'icon' => 'trash',
                    'iconPosition' => 'left',
                    'props' => [],
                    'hasConfirmCallback' => false,
                ]
            ]
        ];

        $this->assertEquals($expected, $group->toArray());
    }
}
