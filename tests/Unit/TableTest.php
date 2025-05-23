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

    public function test_exportable_and_is_exportable()
    {
        $table = new Table();
        $this->assertTrue($table->isExportable()); // Default is true

        $table->exportable(false);
        $this->assertFalse($table->isExportable());

        $table->exportable(true);
        $this->assertTrue($table->isExportable());
    }

    public function test_export_type_and_get_export_type()
    {
        $table = new Table();
        $this->assertEquals('csv', $table->getExportType()); // Default is csv

        $table->exportType('excel');
        $this->assertEquals('excel', $table->getExportType());

        $table->exportType('csv');
        $this->assertEquals('csv', $table->getExportType());
    }

    public function test_export_type_with_invalid_type()
    {
        $table = new Table();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Export type must be either "csv" or "excel"');

        $table->exportType('invalid');
    }

    public function test_export_column_and_get_export_column()
    {
        $table = new Table();
        $this->assertEquals('visible', $table->getExportColumn()); // Default is visible

        $table->exportColumn('all');
        $this->assertEquals('all', $table->getExportColumn());

        $table->exportColumn('visible');
        $this->assertEquals('visible', $table->getExportColumn());
    }

    public function test_export_column_with_invalid_column()
    {
        $table = new Table();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Export column must be either "visible" or "all"');

        $table->exportColumn('invalid');
    }

    public function test_export_name_and_get_export_name()
    {
        $table = new Table();
        // Default export name should start with 'export-' and include the date
        $this->assertStringStartsWith('export-', $table->getExportName());

        $table->exportName('custom-export');
        $this->assertEquals('custom-export', $table->getExportName());
    }
}
