import React from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "../ui/table";
import { Checkbox } from "../ui/checkbox";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";
import { Button } from "../ui/button";
import { DataTableColumnHeader } from "../ui/data-table-column-header";
import { IconRenderer } from './IconRenderer';

interface Column {
  key: string;
  label: string;
  isVisible: boolean;
  hasIcon?: boolean;
  type?: string;
  sortable?: boolean;
  filterable?: boolean;
  toggable?: boolean;
}

interface DatatableBodyProps {
  columns: Column[];
  data: Record<string, React.ReactNode | string | number | boolean | null>[];
  selectedRows: (number | string)[];
  sort?: string;
  direction?: string;
  total: number;
  onSort: (columnKey: string, direction: string) => void;
  onToggleColumnVisibility: (columnKey: string, isVisible: boolean) => void;
  onSelectRow: (rowId: number | string) => void;
  onSelectAllRows: () => void;
  onRowAction: (action: string, rowId: number | string, url?: string, columnKey?: string) => void;
  isRowSelected: (rowId: number | string) => boolean;
  t: (key: string) => string;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const DatatableBody: React.FC<DatatableBodyProps> = ({
  columns,
  data,
  selectedRows,
  sort,
  direction,
  total,
  onSort,
  onToggleColumnVisibility,
  onSelectRow,
  onSelectAllRows,
  onRowAction,
  isRowSelected,
  t,
  icons = {}
}) => {
  const visibleColumns = columns.filter(column => column.isVisible);
  const hasCheckboxColumn = columns.some(col => col.type === 'checkbox');

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            {/* Only show the select all checkbox if there's no checkbox column */}
            {!hasCheckboxColumn && (
              <TableHead className="w-[40px]">
                <Checkbox
                  checked={selectedRows.length === total && total > 0}
                  onCheckedChange={onSelectAllRows}
                />
              </TableHead>
            )}
            {visibleColumns.map((column) => (
              <TableHead
                key={column.key}
                className="cursor-pointer hover:bg-gray-50"
              >
                <DataTableColumnHeader
                  columnKey={column.key}
                  title={column.label}
                  sortKey={sort}
                  sortDirection={direction}
                  isSortable={column.sortable}
                  isToggable={column.toggable}
                  isCheckboxColumn={column.type === 'checkbox'}
                  isAllChecked={column.type === 'checkbox' ? 
                    // For checkbox columns, check if all available (non-disabled) checkboxes are selected
                    data
                      .filter(row => {
                        const value = row[`${column.key}_value`];
                        const isDisabled = row[`${column.key}_disabled`];
                        // Only include rows with valid values that aren't disabled
                        return value !== undefined && value !== null && !isDisabled;
                      })
                      .every(row => isRowSelected(row[`${column.key}_value`] as number | string)) &&
                    // Make sure there's at least one available checkbox
                    data.some(row => {
                      const value = row[`${column.key}_value`];
                      const isDisabled = row[`${column.key}_disabled`];
                      return value !== undefined && value !== null && !isDisabled;
                    })
                    : selectedRows.length === total && total > 0}
                  onCheckboxChange={onSelectAllRows}
                  onSort={(columnKey, sortDirection) => {
                    if (!column.sortable) return;
                    onSort(columnKey, sortDirection);
                  }}
                  onHide={(columnKey) => {
                    onToggleColumnVisibility(columnKey, false);
                  }}
                />
              </TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {data.map((row, index) => (
            <TableRow
              key={row.id !== undefined && row.id !== null ? (typeof row.id === 'object' ? `row-obj-${index}` : String(row.id)) : `row-${index}`}
              className={row.id !== undefined && row.id !== null && typeof row.id !== 'object' && isRowSelected(row.id as number | string) ? "bg-muted/50" : ""}
            >
              {/* Only show the row checkbox if there's no checkbox column AND the row has a valid ID */}
              {!hasCheckboxColumn && row.id !== undefined && row.id !== null && (
                <TableCell>
                  <Checkbox
                    checked={isRowSelected(row.id as number | string)}
                    onCheckedChange={() => onSelectRow(row.id as number | string)}
                  />
                </TableCell>
              )}
              {/* Add an empty cell if there's no checkbox column AND no valid ID */}
              {!hasCheckboxColumn && (row.id === undefined || row.id === null) && (
                <TableCell></TableCell>
              )}
              {visibleColumns.map((column) => {
                const columnKey = column.key;
                const iconName = row[`${columnKey}_icon`] as string | undefined;

                // Handle checkbox columns
                if (column.type === 'checkbox') {
                  const value = row[`${columnKey}_value`];
                  const isDisabled = row[`${columnKey}_disabled`] as boolean;

                  // Only show checkbox if value is valid
                  if (value !== undefined && value !== null) {
                    return (
                      <TableCell key={columnKey}>
                        <Checkbox
                          checked={isRowSelected(value as number | string)}
                          disabled={isDisabled}
                          value={value as string}
                          onCheckedChange={() => onSelectRow(value as number | string)}
                        />
                      </TableCell>
                    );
                  } else {
                    // Return empty cell if no valid value
                    return (
                      <TableCell key={columnKey}></TableCell>
                    );
                  }
                }

                // Handle action columns
                if (column.type === 'action') {
                  const actionData = row[`${columnKey}_action`];
                  if (actionData) {
                    return (
                      <TableCell key={columnKey}>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                              <IconRenderer 
                                iconName={actionData.icon || 'MoreHorizontal'} 
                                className="h-4 w-4 text-current" 
                                icons={icons} 
                              />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            {actionData.actions && actionData.actions.map((action, actionIndex) => {
                              // Check if we need to add a separator after this action
                              const showSeparator = action.separator;

                              return (
                                <React.Fragment key={actionIndex}>
                                  <DropdownMenuItem 
                                    onClick={() => onRowAction(action.name, row._id as number | string, action.url, columnKey)}
                                    className={action.styles || ''}
                                    {...(action.props || {})}
                                  >
                                    {action.icon && action.iconPosition !== 'right' && (
                                      <IconRenderer 
                                        iconName={action.icon} 
                                        className="h-4 w-4 mr-2" 
                                        icons={icons} 
                                      />
                                    )}
                                    {action.label}
                                    {action.icon && action.iconPosition === 'right' && (
                                      <IconRenderer 
                                        iconName={action.icon} 
                                        className="h-4 w-4 ml-2" 
                                        icons={icons} 
                                      />
                                    )}
                                  </DropdownMenuItem>
                                  {showSeparator && <DropdownMenuSeparator />}
                                </React.Fragment>
                              );
                            })}
                            {(!actionData.actions || actionData.actions.length === 0) && (
                              <DropdownMenuItem disabled>{t('no_actions_available')}</DropdownMenuItem>
                            )}
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    );
                  }
                }

                // Handle regular columns
                return (
                  <TableCell key={columnKey}>
                    <div className="flex items-center gap-2">
                      {iconName && <IconRenderer iconName={iconName} className="h-4 w-4" icons={icons} />}
                      {row[columnKey]}
                    </div>
                  </TableCell>
                );
              })}
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
};
