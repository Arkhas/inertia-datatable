<?php

declare(strict_types=1);

namespace Arkhas\InertiaDatatable;

class Table
{
    protected array $columns = [];
    protected array $filters = [];
    protected array $actions = [];
    protected bool $exportable = true;
    protected string $exportType = 'csv';
    protected string $exportColumn = 'visible';
    protected string $exportName = '';

    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function filters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function actions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function exportable(bool $exportable): self
    {
        $this->exportable = $exportable;
        return $this;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function exportType(string $type): self
    {
        if (!in_array($type, ['csv', 'excel'])) {
            throw new \InvalidArgumentException('Export type must be either "csv" or "excel"');
        }
        $this->exportType = $type;
        return $this;
    }

    public function getExportType(): string
    {
        return $this->exportType;
    }

    public function exportColumn(string $column): self
    {
        if (!in_array($column, ['visible', 'all'])) {
            throw new \InvalidArgumentException('Export column must be either "visible" or "all"');
        }
        $this->exportColumn = $column;
        return $this;
    }

    public function getExportColumn(): string
    {
        return $this->exportColumn;
    }

    public function exportName($name): self
    {
        $this->exportName = $name;
        return $this;
    }

    public function getExportName(): string
    {
        return  $this->exportName ?: strtolower('export-' . date('Y-m-d-H-i-s'));
    }

}
