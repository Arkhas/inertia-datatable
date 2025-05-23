<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Services\ExportService;
use Arkhas\InertiaDatatable\Table;
use Arkhas\InertiaDatatable\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestModels\TestModel;
use Tests\TestModels\WithTestModels;

class ExportServiceTest extends TestCase
{
    use WithTestModels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestModels();
    }

    public function test_export_with_all_columns()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Mock Excel facade
        Excel::shouldReceive('download')
            ->once()
            ->andReturn($mockResponse);

        // Create a table with exportable columns
        $table = new Table();
        $table->columns([
            Column::make('name')->exportable(),
            Column::make('status')->exportable(),
        ]);
        $table->exportable(true);
        $table->exportType('excel');
        $table->exportColumn('all');

        // Create export service
        $exportService = new ExportService(TestModel::query(), $table);

        // Test export
        $response = $exportService->export('test');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_export_with_visible_columns()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Mock Excel facade
        Excel::shouldReceive('download')
            ->once()
            ->andReturn($mockResponse);

        // Create a table with exportable columns
        $table = new Table();
        $table->columns([
            Column::make('name')->exportable(),
            Column::make('status')->exportable(),
        ]);
        $table->exportable(true);
        $table->exportType('excel');
        $table->exportColumn('visible');

        // Create export service
        $exportService = new ExportService(TestModel::query(), $table);

        // Set visible columns
        $exportService->withVisibleColumns(['name' => true, 'status' => false]);

        // Test export
        $response = $exportService->export('test');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_export_with_selected_ids()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Mock Excel facade
        Excel::shouldReceive('download')
            ->once()
            ->andReturn($mockResponse);

        // Create a table with exportable columns
        $table = new Table();
        $table->columns([
            Column::make('name')->exportable(),
            Column::make('status')->exportable(),
        ]);
        $table->exportable(true);
        $table->exportType('excel');
        $table->exportColumn('all');

        // Create export service
        $exportService = new ExportService(TestModel::query(), $table);

        // Set selected IDs
        $exportService->withSelectedIds([1, 2]);

        // Test export
        $response = $exportService->export('test');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_export_csv()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Mock Excel facade
        Excel::shouldReceive('download')
            ->once()
            ->andReturn($mockResponse);

        // Create a table with exportable columns
        $table = new Table();
        $table->columns([
            Column::make('name')->exportable(),
            Column::make('status')->exportable(),
        ]);
        $table->exportable(true);
        $table->exportType('csv');
        $table->exportColumn('all');

        // Create export service
        $exportService = new ExportService(TestModel::query(), $table);

        // Test export
        $response = $exportService->export('test');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function test_export_throws_exception_when_not_exportable()
    {
        // Create a table that is not exportable
        $table = new Table();
        $table->exportable(false);

        // Create export service
        $exportService = new ExportService(TestModel::query(), $table);

        // Test export throws exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This table is not exportable');

        $exportService->export('test');
    }

    public function test_export_as()
    {
        $table = new Table();
        $table->columns([
            Column::make('name')->exportAs(fn ($model) => 'foo')->exportable(),
        ]);

        $exportService = new ExportService(TestModel::query(), $table);
        $results = $exportService->prepareData()['data'];

        $this->assertEquals('foo', $results[0][0]);
        $this->assertEquals('foo', $results[1][0]);
        $this->assertEquals('foo', $results[2][0]);
    }

    public function test_with_export_type()
    {
        // Create a table with CSV as default export type
        $table = new Table();
        $table->columns([
            Column::make('name')->exportable(),
        ]);
        $table->exportable(true);

        // Create export service
        $exportService = new ExportService(TestModel::query(), $table);

        // Initially, the export type should be CSV (from the table)
        $this->assertEquals('csv', $this->getProtectedProperty($exportService, 'exportType'));

        // Override the export type to Excel
        $exportService->withExportType('excel');

        // Now the export type should be Excel
        $this->assertEquals('excel', $this->getProtectedProperty($exportService, 'exportType'));
    }

    /**
     * Helper method to get protected/private property value
     */
    private function getProtectedProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
    }
}
