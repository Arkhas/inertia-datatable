<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\TestModels\TestModelDataTable;
use Tests\TestModels\WithTestModels;
use Tests\TestModels\TestModel;
use Arkhas\InertiaDatatable\EloquentTable;
use Arkhas\InertiaDatatable\Columns\Column;
use Arkhas\InertiaDatatable\InertiaDatatable;
use Arkhas\InertiaDatatable\Services\ExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;

class InertiaDatatableExportTest extends TestCase
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

        // Create a partial mock of TestModelDataTable
        $datatable = $this->getMockBuilder(TestModelDataTable::class)
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
}
