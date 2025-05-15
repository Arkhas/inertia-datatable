<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Table;
use Arkhas\InertiaDatatable\Columns\Column;
use Arkhas\InertiaDatatable\Filters\Filter;

class TableTest extends TestCase
{
    public function test_columns_and_get_columns()
    {
        $col1 = Column::make('foo');
        $col2 = Column::make('bar');
        $table = (new Table())->columns([$col1, $col2]);
        $this->assertEquals([$col1, $col2], $table->getColumns());
    }

    public function test_filters_and_get_filters()
    {
        $filter1 = Filter::make('status');
        $filter2 = Filter::make('type');
        $table = (new Table())->filters([$filter1, $filter2]);
        $this->assertEquals([$filter1, $filter2], $table->getFilters());
    }

    public function test_actions_and_get_actions()
    {
        $action1 = \Arkhas\InertiaDatatable\Actions\TableAction::make('edit');
        $action2 = \Arkhas\InertiaDatatable\Actions\TableAction::make('delete');
        $table = (new Table())->actions([$action1, $action2]);
        $this->assertEquals([$action1, $action2], $table->getActions());
    }
}
