<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Columns\Column;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;

class ColumnExportTest extends TestCase
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

    public function test_exportable_and_is_exportable()
    {
        $column = Column::make('name');

        // Default should be exportable
        $this->assertTrue($column->isExportable());

        // Set to not exportable
        $column->exportable(false);
        $this->assertFalse($column->isExportable());

        // Set back to exportable
        $column->exportable(true);
        $this->assertTrue($column->isExportable());
    }

    public function test_export_as_and_get_export_value()
    {
        $column = Column::make('name')->exportAs(fn($model) => strtoupper($model->name));
        $model = TestModel::first();

        // Should use the export callback
        $this->assertEquals(strtoupper($model->name), $column->getExportValue($model));

        // Without export callback, should use renderHtml
        $column = Column::make('name');
        $this->assertEquals($model->name, $column->getExportValue($model));
    }

    public function test_to_array_includes_all_properties()
    {
        $column = Column::make('name')
            ->label('Name')
            ->icon(fn($model) => 'user', 'right')
            ->sortable(false)
            ->searchable(false)
            ->toggable(false)
            ->width('100px');

        $array = $column->toArray();

        $this->assertEquals('name', $array['name']);
        $this->assertEquals('Name', $array['label']);
        $this->assertTrue($array['hasIcon']);
        $this->assertEquals('right', $array['iconPosition']);
        $this->assertFalse($array['sortable']);
        $this->assertFalse($array['searchable']);
        $this->assertFalse($array['toggable']);
        $this->assertEquals('100px', $array['width']);
    }

    public function test_apply_filter_with_array_input()
    {
        $column = Column::make('status');
        $query = TestModel::query();

        // Apply filter with array of keywords
        $column->applyFilter($query, ['active', 'inactive']);

        // Should generate SQL with multiple OR conditions
        $sql = $query->toSql();
        $this->assertStringContainsString('where', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('like', $sql);
    }

    public function test_build_nested_where_has_with_multiple_levels()
    {
        // Create a column with a deep relation path
        $column = Column::make('user.team.name');

        $this->assertTrue($column->hasRelation());
        $this->assertEquals(['user', 'team'], $column->getRelationPath());
        $this->assertEquals('name', $column->getName());

        // Test the applyFilter method which uses buildNestedWhereHas internally
        $query = TestModel::query();
        $column->applyFilter($query, 'Engineering');

        // Check that the SQL contains the expected whereHas clauses
        $sql = $query->toSql();
        $this->assertStringContainsString('exists', $sql);
        $this->assertStringContainsString('users', $sql);
    }
}
