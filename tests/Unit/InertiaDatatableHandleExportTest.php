<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;
use Tests\TestModels\TestModelDataTable;
use Arkhas\InertiaDatatable\EloquentTable;
use Arkhas\InertiaDatatable\Columns\Column;
use Arkhas\InertiaDatatable\InertiaDatatable;
use Arkhas\InertiaDatatable\Services\ExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

// Test-specific subclass of InertiaDatatable that makes handleExport public
class TestableInertiaDatatable extends InertiaDatatable
{
    public function setup(): void
    {
        // This method is required by the abstract class
    }

    public function handleExport(): BinaryFileResponse
    {
        return parent::handleExport();
    }
}

class InertiaDatatableHandleExportTest extends TestCase
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

    public function test_render_calls_handle_export_when_export_param_is_present()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Create a partial mock of TestableInertiaDatatable
        $datatable = $this->getMockBuilder(TestableInertiaDatatable::class)
            ->onlyMethods(['handleExport'])
            ->getMock();

        // Expect handleExport to be called once and return our mock response
        $datatable->expects($this->once())
            ->method('handleExport')
            ->willReturn($mockResponse);

        // Create a table with exportable columns
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name')->exportable(),
            Column::make('status')->exportable(),
        ]);
        $table->exportable(true);
        $datatable->table($table);

        // Make a request with the export parameter
        $request = new Request(['export' => true]);
        $this->app->instance(Request::class, $request);

        // Call render which should trigger handleExport
        $response = $datatable->render('Datatable');

        // Verify that the response is the mock BinaryFileResponse
        $this->assertSame($mockResponse, $response);
    }

    public function test_handle_export_not_exportable()
    {
        // Create a datatable
        $datatable = new TestModelDataTable();

        // Create a table that is not exportable
        $table = EloquentTable::make(TestModel::query())->columns([
            Column::make('name'),
            Column::make('status'),
        ]);
        $table->exportable(false);
        $datatable->table($table);

        // Make a request with the export parameter
        $request = new Request(['export' => true]);
        $this->app->instance(Request::class, $request);

        // Expect an abort with 403
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('This table is not exportable');

        // Call render which should trigger handleExport
        $datatable->render('Datatable');
    }

    public function test_handle_export_with_all_options()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Create a mock ExportService
        $mockExportService = $this->createMock(ExportService::class);
        $mockExportService->method('withExportType')->willReturnSelf();
        $mockExportService->method('withSelectedIds')->willReturnSelf();
        $mockExportService->method('withVisibleColumns')->willReturnSelf();
        $mockExportService->method('export')->willReturn($mockResponse);

        // Create a partial mock of TestableInertiaDatatable
        $datatable = $this->getMockBuilder(TestableInertiaDatatable::class)
            ->onlyMethods(['handleExport'])
            ->getMock();

        // Expect handleExport to be called once and return our mock response
        $datatable->expects($this->once())
            ->method('handleExport')
            ->willReturn($mockResponse);

        // Create a table with exportable columns
        $queryBuilder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $table = EloquentTable::make($queryBuilder);
        $table->exportable(true);
        $datatable->table($table);

        // Create a request with export parameters
        $request = new Request([
            'export' => true,
            'exportType' => 'excel',
            'exportColumns' => 'visible',
            'exportRows' => 'selected',
            'selectedIds' => '1,2,3'
        ]);
        $this->app->instance(Request::class, $request);

        // Call render which should trigger handleExport
        $response = $datatable->render('Datatable');

        // Verify that the response is the mock BinaryFileResponse
        $this->assertSame($mockResponse, $response);
    }

    public function test_handle_export_implementation()
    {
        // Create a mock BinaryFileResponse
        $mockResponse = $this->createMock(BinaryFileResponse::class);

        // Create a real TestModel query builder that supports cloning
        $query = TestModel::query();

        // Create a datatable with a partial mock to handle the session
        $datatable = $this->getMockBuilder(TestableInertiaDatatable::class)
            ->onlyMethods(['getFromSession'])
            ->getMock();

        // Mock getFromSession to return appropriate values for different keys
        $datatable->expects($this->any())
            ->method('getFromSession')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'visibleColumns') {
                    return ['name' => true, 'status' => false];
                } elseif ($key === 'filters') {
                    return [];
                }
                return $default;
            });

        // Create a table with exportable columns
        $table = EloquentTable::make($query);
        $table->columns([
            \Arkhas\InertiaDatatable\Columns\Column::make('name')->exportable(),
            \Arkhas\InertiaDatatable\Columns\Column::make('status')->exportable(),
        ]);
        $table->exportable(true);
        $table->exportType('csv');
        $table->exportColumn('visible');
        $table->exportName('test-export');
        $datatable->table($table);

        // Create a request with export parameters
        $request = new Request([
            'export' => true,
            'exportType' => 'excel',
            'exportColumns' => 'visible',
            'exportRows' => 'selected',
            'selectedIds' => '1,2,3'
        ]);
        $this->app->instance(Request::class, $request);

        // Mock the Excel facade to return our mock response
        \Maatwebsite\Excel\Facades\Excel::shouldReceive('download')
            ->once()
            ->andReturn($mockResponse);

        // Call handleExport
        $response = $datatable->handleExport();

        // Verify that the response is the mock BinaryFileResponse
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }
}
