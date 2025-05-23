<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable\Services;

use Arkhas\InertiaDatatable\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportService
{
    protected Builder $query;
    protected Table $table;
    protected string $exportType;
    protected string $exportColumn;
    protected array $selectedIds = [];
    protected ?array $visibleColumns = null;

    public function __construct(Builder $query, Table $table)
    {
        $this->query = $query;
        $this->table = $table;
        $this->exportType = $table->getExportType();
        $this->exportColumn = $table->getExportColumn();
    }

    public function withSelectedIds(array $ids): self
    {
        $this->selectedIds = $ids;
        return $this;
    }

    public function withVisibleColumns(?array $visibleColumns): self
    {
        $this->visibleColumns = $visibleColumns;
        return $this;
    }

    public function export(string $filename = 'export')
    {
        if (!$this->table->isExportable()) {
            throw new \Exception('This table is not exportable');
        }

        // Create a new exporter class
        $exporter = new class($this->prepareData()) implements FromCollection, WithHeadings {
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
        };

        // Export based on the selected type
        if ($this->exportType === 'excel') {
            return Excel::download($exporter, $filename . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        } else {
            return Excel::download($exporter, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }
    }

    protected function prepareData(): array
    {
        // Get all columns or only visible columns
        $columns = $this->table->getColumns();

        if ($this->exportColumn === 'visible' && $this->visibleColumns !== null) {
            // Filter out non-visible columns based on the visibleColumns array
            $columns = array_filter($columns, function ($column) {
                $columnName = $column->getName();
                // Only include columns that are both exportable and visible
                return $column->isExportable() && 
                       (isset($this->visibleColumns[$columnName]) && $this->visibleColumns[$columnName] === true);
            });
        } else {
            // Use all columns that are exportable
            $columns = array_filter($columns, function ($column) {
                return $column->isExportable();
            });
        }

        // Prepare the query
        $query = clone $this->query;

        // If we have selected IDs, filter the query
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        // Get the data
        $models = $query->get();

        // Prepare headings
        $headings = array_map(function ($column) {
            return $column->getLabel() ?: $column->getName();
        }, $columns);

        // Prepare data rows
        $data = [];
        foreach ($models as $model) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $column->getExportValue($model);
            }
            $data[] = $row;
        }

        return [
            'headings' => $headings,
            'data' => $data,
        ];
    }
}
