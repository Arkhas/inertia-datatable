import React from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from "../ui/table";
import {DataTableColumnHeader} from "./DataTableColumnHeader";
import {Column as ColumnType} from "./types";
import {Column} from "./columns/Column";
import {CheckboxColumn} from "./columns/CheckboxColumn";
import {ActionColumn} from "./columns/ActionColumn";
import {ActionGroupColumn} from "./columns/ActionGroupColumn";

interface DatatableBodyProps {
    columns: ColumnType[];
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

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        {visibleColumns.map((column) => (
                            <TableHead
                                key={column.key}
                                className="hover:bg-muted/50"
                                style={column.width ? {width: column.width} : {}}
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
                    {data.length === 0 && (
                        <TableRow>
                            <TableCell colSpan={columns.length} className="h-24 text-center">
                                {t('no_results')}
                            </TableCell>
                        </TableRow>
                    )}
                    {data.map((row, index) => (
                        <TableRow
                            key={row.id !== undefined && row.id !== null ? (typeof row.id === 'object' ? `row-obj-${index}` : String(row.id)) : `row-${index}`}
                            className={row.id !== undefined && row.id !== null && typeof row.id !== 'object' && isRowSelected(row.id as number | string) ? "bg-muted/50" : ""}
                        >
                            {visibleColumns.map((column) => {
                                const columnKey = column.key;

                                // Render the appropriate column component based on type
                                if (column.type === 'checkbox') {
                                    return (
                                        <CheckboxColumn
                                            key={columnKey}
                                            columnKey={columnKey}
                                            row={row}
                                            isRowSelected={isRowSelected}
                                            onSelectRow={onSelectRow}
                                        />
                                    );
                                }

                                if (column.type === 'action') {
                                    const actionData = row[`${columnKey}_action`];
                                    if (actionData && actionData.actions) {
                                        // If there's only one action, use ActionColumn
                                        if (actionData.actions.length === 1) {
                                            return (
                                                <ActionColumn
                                                    key={columnKey}
                                                    columnKey={columnKey}
                                                    row={row}
                                                    onRowAction={onRowAction}
                                                    icons={icons}
                                                />
                                            );
                                        }

                                        // Otherwise use ActionGroupColumn for multiple actions
                                        return (
                                            <ActionGroupColumn
                                                key={columnKey}
                                                columnKey={columnKey}
                                                row={row}
                                                onRowAction={onRowAction}
                                                t={t}
                                                icons={icons}
                                            />
                                        );
                                    }
                                }

                                // Default to regular Column
                                return (
                                    <Column
                                        key={columnKey}
                                        columnKey={columnKey}
                                        row={row}
                                        column={column}
                                        icons={icons}
                                    />
                                );
                            })}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
};
