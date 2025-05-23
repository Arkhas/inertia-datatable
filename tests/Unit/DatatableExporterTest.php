<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Services\DatatableExporter;
use Illuminate\Support\Collection;

class DatatableExporterTest extends TestCase
{
    public function test_collection_returns_data()
    {
        $data = [
            ['John', 'Doe', 'john.doe@example.com'],
            ['Jane', 'Doe', 'jane.doe@example.com'],
        ];
        
        $headings = ['First Name', 'Last Name', 'Email'];
        
        $exportData = [
            'data' => $data,
            'headings' => $headings,
        ];
        
        $exporter = new DatatableExporter($exportData);
        
        $this->assertInstanceOf(Collection::class, $exporter->collection());
        $this->assertEquals($data, $exporter->collection()->toArray());
    }
    
    public function test_headings_returns_headings()
    {
        $data = [
            ['John', 'Doe', 'john.doe@example.com'],
            ['Jane', 'Doe', 'jane.doe@example.com'],
        ];
        
        $headings = ['First Name', 'Last Name', 'Email'];
        
        $exportData = [
            'data' => $data,
            'headings' => $headings,
        ];
        
        $exporter = new DatatableExporter($exportData);
        
        $this->assertEquals($headings, $exporter->headings());
    }
}