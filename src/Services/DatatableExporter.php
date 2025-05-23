<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Services;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DatatableExporter implements FromCollection, WithHeadings
{
    protected Collection $data;
    protected array $headings;

    public function __construct(array $exportData)
    {
        $this->data = collect($exportData['data']);
        $this->headings = $exportData['headings'];
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}