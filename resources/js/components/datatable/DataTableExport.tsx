import React, { useState } from "react";
import { Download } from "lucide-react";
import { useTranslation } from "../../lib/useTranslation";
import { Button } from "../ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "../ui/dialog";
import {
  ToggleGroup,
  ToggleGroupItem,
} from "../ui/toggle-group";

interface DataTableExportProps {
  onExport: (options: ExportOptions) => void;
  selectedRows: (number | string)[];
  defaultExportType?: string;
  defaultExportColumn?: string;
}

export interface ExportOptions {
  type: string;
  columns: string;
  rows: string;
}

export function DataTableExport({
  onExport,
  selectedRows,
  defaultExportType = "csv",
  defaultExportColumn = "visible",
}: DataTableExportProps) {
  const { t } = useTranslation();
  const [open, setOpen] = useState(false);
  const [exportType, setExportType] = useState<string>(defaultExportType);
  const [exportColumns, setExportColumns] = useState<string>(defaultExportColumn);
  const [exportRows, setExportRows] = useState<string>("all");

  const hasSelectedRows = selectedRows.length > 0;

  const handleExport = () => {
    onExport({
      type: exportType,
      columns: exportColumns,
      rows: exportRows,
    });
    setOpen(false);
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className="h-8 lg:flex"
        >
          <Download className="h-4 w-4 text-current" />
          <span className="ml-2 lg:block hidden">{t('export')}</span>
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{t('export_data')}</DialogTitle>
          <DialogDescription>
            {t('export_description')}
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-4 py-4">
          <div className="grid gap-2">
            <label className="text-sm font-medium">{t('export_format')}</label>
            <ToggleGroup
                variant="outline"
              type="single" 
              value={exportType}
              onValueChange={(value) => {
                if (value) setExportType(value);
              }}
              className="justify-start"
            >
              <ToggleGroupItem className={"w-full cursor-pointer"} value="excel" aria-label="Excel">
                Excel
              </ToggleGroupItem>
              <ToggleGroupItem className={"w-full cursor-pointer"} value="csv" aria-label="CSV">
                CSV
              </ToggleGroupItem>
            </ToggleGroup>
          </div>
          <div className="grid gap-2">
            <label className="text-sm font-medium">{t('export_columns')}</label>
            <ToggleGroup
                variant="outline"
              type="single" 
              value={exportColumns}
              onValueChange={(value) => {
                if (value) setExportColumns(value);
              }}
              className="justify-start"
            >
              <ToggleGroupItem className={"w-full cursor-pointer"} value="visible" aria-label="Visible Columns">
                {t('visible_columns')}
              </ToggleGroupItem>
              <ToggleGroupItem className={"w-full cursor-pointer"} value="all" aria-label="All Columns">
                {t('all_columns')}
              </ToggleGroupItem>
            </ToggleGroup>
          </div>
          <div className="grid gap-2">
            <label className="text-sm font-medium">{t('export_rows')}</label>
            <ToggleGroup
                variant="outline"
              type="single" 
              value={exportRows}
              onValueChange={(value) => {
                if (value) setExportRows(value);
              }}
              className="justify-start"
            >
              <ToggleGroupItem className={"w-full cursor-pointer"} value="all" aria-label="All Rows">
                {t('all_rows')}
              </ToggleGroupItem>
              <ToggleGroupItem
                  className={"w-full cursor-pointer"}
                value="selected" 
                aria-label="Selected Rows"
                disabled={!hasSelectedRows}
              >
                {t('selected_rows')} {hasSelectedRows && `(${selectedRows.length})`}
              </ToggleGroupItem>
            </ToggleGroup>
          </div>
        </div>
        <DialogFooter>
          <Button type="submit" onClick={handleExport}>
            {t('export')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}