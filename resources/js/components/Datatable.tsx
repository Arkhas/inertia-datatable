import React, { useState, useEffect, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTranslation, updateTranslations } from '../lib/useTranslation';
import { Toaster, toast } from 'sonner';
// Import UI components from the consuming application
// The @/ path alias is resolved by the consuming application's bundler
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from "./ui/table";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import { Checkbox } from "./ui/checkbox";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "./ui/dropdown-menu";
// Import icons from the consuming application
// These should be provided by the consuming application
// We'll use React.ComponentType for the icon types
import type { SVGProps } from 'react';
import { DataTableColumnHeader } from "./ui/data-table-column-header";
import { DataTableViewOptions } from "./ui/data-table-view-options";
import { DataTablePagination } from "./ui/data-table-pagination";
import { DataTableFacetedFilter } from "./ui/data-table-faceted-filter";
import { getIconByName } from "../lib/utils";

// Define types for the page props
interface Column {
    name: string;
    label?: string;
    hasIcon?: boolean;
    type?: string;
    sortable?: boolean;
    filterable?: boolean;
    toggable?: boolean;
}

interface TableAction {
    type: 'action';
    name: string;
    label: string;
    styles?: string;
    icon?: string;
    props?: Record<string, any>;
}

interface TableActionGroup {
    type: 'group';
    name: string;
    label: string;
    styles?: string;
    icon?: string;
    iconPosition?: string;
    props?: Record<string, any>;
    actions: TableAction[];
}

interface FilterDefinition {
    name: string;
    label: string;
    options: Record<string, string>;
    icons?: Record<string, string>;
    multiple: boolean;
}

interface PageProps {
    columns: Column[];
    filters: FilterDefinition[];
    actions: (TableAction | TableActionGroup)[];
    currentFilters: Record<string, string | string[]>;
    data: {
        data: Record<string, unknown>[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    pageSize: number;
    availablePageSizes: number[];
    sort?: string;
    direction?: string;
    translations?: Record<string, Record<string, string>>;
    actionResult?: {
        success?: boolean;
        message?: string;
        title?: string;
        variant?: string;
    };
}

// Define types for icon components
type IconComponent = React.ComponentType<React.SVGProps<SVGSVGElement>>;

interface DatatableProps {
    route?: string;
    // Icon components (optional)
    icons?: Record<string, IconComponent>;
}

const Datatable: React.FC<DatatableProps> = ({ route: routeName, icons = {} }) => {
    // Get data from Inertia page props
    const { columns, filters, actions, currentFilters, data, pageSize, availablePageSizes, sort, direction, translations, actionResult } = usePage().props as PageProps;
    // Get translation function
    const { t } = useTranslation();
    // Track if an action was explicitly triggered
    const [actionTriggered, setActionTriggered] = useState(false);

    // Load translations when component mounts
    useEffect(() => {
        if (translations) {
            updateTranslations(translations);
        }
    }, [translations]);


    // State for visible columns and selected rows
    const [visibleColumns, setVisibleColumns] = useState<Record<string, boolean>>({});
    const [selectedRows, setSelectedRows] = useState<(number | string)[]>([]);
    const [selectedFilterValues, setSelectedFilterValues] = useState<Record<string, Set<string>>>({});

    // Helper function to check if a row is selected
    const isRowSelected = (rowId: number | string): boolean => {
        return selectedRows.some(id => String(id) === String(rowId));
    };

    // Format columns for display
    const formattedColumns = columns ? columns.map(column => ({
        key: column.name,
        label: column.label || column.name.charAt(0).toUpperCase() + column.name.slice(1).replace(/_/g, ' '),
        isVisible: visibleColumns[column.name],
        hasIcon: column.hasIcon,
        type: column.type,
        sortable: column.sortable !== undefined ? column.sortable : true,
        filterable: column.filterable !== undefined ? column.filterable : true,
        toggable: column.toggable !== undefined ? column.toggable : true
    })) : [];

    // Format data for display
    const formattedData = data?.data ? data.data.map(item => {
        const row: Record<string, React.ReactNode | string | number | boolean | null> = {};

        // Store the original ID value from the server
        if (item.id !== undefined) {
            row._id = item.id;
        }

        columns.forEach(column => {
            const columnName = column.name;
            if (item[`${columnName}_html`]) {
                row[columnName] = <div dangerouslySetInnerHTML={{ __html: item[`${columnName}_html`] }} />;
            } else {
                row[columnName] = item[columnName];
            }

            // Store icon information if available
            if (item[`${columnName}_icon`]) {
                row[`${columnName}_icon`] = item[`${columnName}_icon`];
            }

            // Store checkbox column properties if available
            if (column.type === 'checkbox') {
                if (item[`${columnName}_value`] !== undefined) {
                    row[`${columnName}_value`] = item[`${columnName}_value`];
                }
                if (item[`${columnName}_checked`] !== undefined) {
                    row[`${columnName}_checked`] = item[`${columnName}_checked`];
                }
                if (item[`${columnName}_disabled`] !== undefined) {
                    row[`${columnName}_disabled`] = item[`${columnName}_disabled`];
                }
            }

            // Store action column properties if available
            if (column.type === 'action' && item[`${columnName}_action`]) {
                row[`${columnName}_action`] = item[`${columnName}_action`];
            }
        });
        return row;
    }) : [];

    // Initialize visible columns on the first render
    useEffect(() => {
        if (columns) {
            const initialVisibleColumns: Record<string, boolean> = {};
            columns.forEach(column => {
                initialVisibleColumns[column.name] = true;
            });
            setVisibleColumns(initialVisibleColumns);
        }
    }, [columns]);

    // Initialize filter values from currentFilters
    useEffect(() => {
        if (currentFilters) {
            const initialValues: Record<string, Set<string>> = {};
            Object.entries(currentFilters).forEach(([filterName, filterValue]) => {
                initialValues[filterName] = Array.isArray(filterValue)
                    ? new Set<string>(filterValue)
                    : new Set<string>([filterValue as string]);
            });
            setSelectedFilterValues(initialValues);
        }
    }, [currentFilters]);

    // Track if selectedRows has been initialized
    const initializedRef = useRef(false);
    // Track if we have data available
    const dataAvailableRef = useRef(false);

    // Initialize selectedRows with pre-selected checkboxes
    useEffect(() => {
        // Reset the initialization flag when the component unmounts
        return () => {
            initializedRef.current = false;
            dataAvailableRef.current = false;
        };
    }, []);

    // This effect will run once when data is available
    useEffect(() => {
        // Check if we have data and haven't initialized yet
        if (!initializedRef.current && formattedData && formattedData.length > 0 && columns && columns.length > 0) {
            // Mark that we have data available
            dataAvailableRef.current = true;

            const checkboxColumn = columns.find(col => col.type === 'checkbox');
            if (checkboxColumn) {
                const columnName = checkboxColumn.name;
                const preSelectedRows = formattedData
                    .filter(row => {
                        // Only include rows that are checked and not disabled
                        return row[`${columnName}_checked`] === true && row[`${columnName}_disabled`] !== true;
                    })
                    .map(row => row[`${columnName}_value`])
                    .filter(value => value !== undefined && value !== null);

                // Only update state if we have pre-selected rows
                if (preSelectedRows.length > 0) {
                    setSelectedRows(preSelectedRows);
                }

                // Mark as initialized
                initializedRef.current = true;
            }
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [formattedData, columns]);

    // Show toast notification when action result is available
    useEffect(() => {
        if (actionResult && actionTriggered) {
            const { success, message, title, variant } = actionResult;

            if (message) {
                if (variant) {
                    // Use the specified variant
                    toast(title || (success ? t('success') : t('error')), {
                        description: message,
                        variant: variant as any
                    });
                } else if (success === true) {
                    // Success toast
                    toast.success(title || t('success'), {
                        description: message
                    });
                } else if (success === false) {
                    // Error toast
                    toast.error(title || t('error'), {
                        description: message
                    });
                } else {
                    // Default toast
                    toast(title || t('notification'), {
                        description: message
                    });
                }

                // Reset actionTriggered after showing the notification
                setActionTriggered(false);
            }
        }
    }, [actionResult, actionTriggered, t]);

    // Handle datatable actions
    const handleDatatableAction = (action: string, params: Record<string, unknown> = {}) => {
        const routeParams: Record<string, number> = {};

        // Note: If you modify this function to include 'actionResult' in the 'only' array,
        // you should set actionTriggered to true before sending the request:
        // setActionTriggered(true);

        // Add page parameter if provided
        if (params.page && typeof params.page === 'number') {
            routeParams.page = params.page;
        }

        // Build query parameters
        const queryParams: Record<string, unknown> = {
            pageSize: params.pageSize || pageSize
        };

        // Add sort parameters if provided
        if (params.sort && typeof params.sort === 'string') {
            queryParams.sort = params.sort;
            queryParams.direction = (params.direction && typeof params.direction === 'string')
                ? params.direction
                : 'asc';
        }

        // Add search parameter if provided
        if (params.search && typeof params.search === 'string') {
            queryParams.search = params.search;
        }

        // Add filter parameters if provided
        if (params.filters && typeof params.filters === 'object') {
            queryParams.filters = params.filters;
        }

        // Handle column visibility toggle
        if (action === 'toggleColumnVisibility' && params.columnKey && typeof params.isVisible === 'boolean') {
            // Find the column to check if it's toggable
            const column = formattedColumns.find(col => col.key === params.columnKey);

            // Only toggle visibility if the column is toggable
            if (column && (column.toggable !== false)) {
                setVisibleColumns(prev => ({
                    ...prev,
                    [params.columnKey as string]: params.isVisible as boolean
                }));
            }
        }

        // Navigate to the route for actions that require server interaction
        if (['sort', 'search', 'pageSize', 'filter', 'resetFilters', 'prevPage', 'nextPage', 'firstPage', 'lastPage'].includes(action)) {
            const url = route(routeName, routeParams);

            // Store current selectedRows before making the request
            const currentSelectedRows = [...selectedRows];

            router.visit(url, {
                method: 'post',
                data: queryParams,
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['data', 'currentFilters', 'sort', 'direction', 'pageSize'],
                onSuccess: () => {
                    // After data is loaded, update selectedRows to only include IDs that still exist in the new data
                    const checkboxColumn = columns.find(col => col.type === 'checkbox');
                    if (checkboxColumn) {
                        const columnName = checkboxColumn.name;
                        // Get all valid row IDs from the new data
                        const validRowIds = formattedData
                            .map(row => {
                                const value = row[`${columnName}_value`];
                                const isDisabled = row[`${columnName}_disabled`];
                                // Only include rows with valid values that aren't disabled
                                return (isDisabled || value === undefined || value === null) ? null : value;
                            })
                            .filter(id => id !== null);

                        // Filter selectedRows to only include IDs that exist in the new data
                        const updatedSelectedRows = currentSelectedRows.filter(id => 
                            validRowIds.includes(id)
                        );

                        // Update selectedRows state
                        setSelectedRows(updatedSelectedRows);
                    }
                }
            });
        }
    };

    // Map filter options to the format expected by DataTableFacetedFilter
    const getFilterOptions = (filterName: string) => {
        const filter = filters?.find(f => f.name === filterName);
        if (!filter) return [];

        return Object.entries(filter.options).map(([value, label]) => {
            // Get icon from filter.icons if available
            const iconName = filter.icons?.[value];
            const icon = iconName ? getIconComponent(iconName) : undefined;

            return {
                label,
                value,
                icon
            };
        });
    };

    const handleFilterChange = (filterName: string, values: string[]) => {
        // Update the selected filter values in state
        const newSelectedFilterValues = {
            ...selectedFilterValues,
            [filterName]: new Set(values)
        };
        setSelectedFilterValues(newSelectedFilterValues);

        // Format all active filters as filters[status]=todo,in_progress
        const filters: Record<string, string> = {};

        // Include all active filters, not just the one being changed
        Object.entries(newSelectedFilterValues).forEach(([name, valueSet]) => {
            const filterValues = Array.from(valueSet);
            if (filterValues.length > 0) {
                filters[name] = filterValues.join(',');
            }
        });

        handleDatatableAction('filter', { filters });
    };

    const handleResetFilters = () => {
        setSelectedFilterValues({});
        handleDatatableAction('resetFilters');
    };

    const handleSelectRow = (rowId: number | string) => {
        setSelectedRows((prev) => {
            if (isRowSelected(rowId)) {
                return prev.filter((id) => String(id) !== String(rowId));
            } else {
                return [...prev, rowId];
            }
        });
    };

    const handleSelectAllRows = () => {
        // Get all row IDs from checkbox columns if available
        const checkboxColumn = columns.find(col => col.type === 'checkbox');
        if (checkboxColumn) {
            const columnName = checkboxColumn.name;

            // Get all available (non-disabled) checkbox values
            const availableRowIds = formattedData
                .map((row) => {
                    const value = row[`${columnName}_value`];
                    const isDisabled = row[`${columnName}_disabled`];
                    // Only include rows with valid values that aren't disabled
                    return (isDisabled || value === undefined || value === null) ? null : value;
                })
                .filter(id => id !== null);

            // Check if all available checkboxes are already selected
            const allAvailableSelected = availableRowIds.length > 0 && 
                availableRowIds.every(id => isRowSelected(id));

            if (allAvailableSelected) {
                // If all available are selected, deselect them
                const newSelectedRows = selectedRows.filter(id => !availableRowIds.some(availableId => String(id) === String(availableId)));
                setSelectedRows(newSelectedRows);
            } else {
                // If not all available are selected, select all available
                const newSelectedRows = [...selectedRows];
                availableRowIds.forEach(id => {
                    if (!isRowSelected(id)) {
                        newSelectedRows.push(id);
                    }
                });
                setSelectedRows(newSelectedRows);
            }
        } else {
            // For non-checkbox columns, use the original logic
            if (selectedRows.length === formattedData.length) {
                setSelectedRows([]);
            } else {
                // Fallback to using only valid row.id values (no index fallback)
                const validRowIds = formattedData
                    .map((row) => {
                        if (row.id !== undefined && row.id !== null) {
                            // Handle case where row.id is an object
                            return typeof row.id === 'object' ? null : row.id;
                        }
                        return null;
                    })
                    .filter(id => id !== null);
                setSelectedRows(validRowIds);
            }
        }
    };

    // Handle row actions
    const handleRowAction = (action: string, taskId: number, url?: string): void => {
        console.log(`Action ${action} on task ${taskId}`);

        // If a URL is provided, navigate to it
        if (url) {
            window.location.href = url;
            return;
        }

        // Otherwise, send the action to the server
        if (!routeName) {
            console.error("Route name is not defined for Datatable component");
            return;
        }

        try {
            const actionUrl = route(routeName);
            console.log(`Sending action ${action} for ID:`, taskId);
            // Set actionTriggered to true to indicate that an action was explicitly triggered
            setActionTriggered(true);

            // Store current selectedRows before making the request
            const currentSelectedRows = [...selectedRows];

            router.post(actionUrl, {
                action: action,
                ids: [taskId]
            }, {
                preserveState: true,
                preserveScroll: true,
                only: ['data', 'actionResult'],
                onSuccess: () => {
                    // After data is loaded, update selectedRows to only include IDs that still exist in the new data
                    const checkboxColumn = columns.find(col => col.type === 'checkbox');
                    if (checkboxColumn) {
                        const columnName = checkboxColumn.name;
                        // Get all valid row IDs from the new data
                        const validRowIds = formattedData
                            .map(row => {
                                const value = row[`${columnName}_value`];
                                const isDisabled = row[`${columnName}_disabled`];
                                // Only include rows with valid values that aren't disabled
                                return (isDisabled || value === undefined || value === null) ? null : value;
                            })
                            .filter(id => id !== null);

                        // Filter selectedRows to only include IDs that exist in the new data
                        const updatedSelectedRows = currentSelectedRows.filter(id => 
                            validRowIds.includes(id)
                        );

                        // Update selectedRows state
                        setSelectedRows(updatedSelectedRows);
                    }
                }
            });
        } catch (error) {
            console.error("Error sending action to server:", error);
        }
    };

    // Handle bulk actions
    const handleBulkAction = (actionName: string): void => {
        if (selectedRows.length === 0) {
            return;
        }

        // Get the selected row IDs
        let selectedIds: (number | string)[] = [];

        // Use the selected row IDs directly - these should already be the correct IDs
        // from either the checkbox column or the row.id
        selectedIds = selectedRows;

        // Send the action to the server
        if (!routeName) {
            console.error("Route name is not defined for Datatable component");
            return;
        }

        try {
            const url = route(routeName);
            console.log(`Sending action ${actionName} with IDs:`, selectedIds);
            // Set actionTriggered to true to indicate that an action was explicitly triggered
            setActionTriggered(true);

            // Store current selectedRows before making the request
            const currentSelectedRows = [...selectedRows];

            router.post(url, {
                action: actionName,
                ids: selectedIds
            }, {
                preserveState: true,
                preserveScroll: true,
                only: ['data', 'actionResult'],
                onSuccess: () => {
                    // After data is loaded, update selectedRows to only include IDs that still exist in the new data
                    const checkboxColumn = columns.find(col => col.type === 'checkbox');
                    if (checkboxColumn) {
                        const columnName = checkboxColumn.name;
                        // Get all valid row IDs from the new data
                        const validRowIds = formattedData
                            .map(row => {
                                const value = row[`${columnName}_value`];
                                const isDisabled = row[`${columnName}_disabled`];
                                // Only include rows with valid values that aren't disabled
                                return (isDisabled || value === undefined || value === null) ? null : value;
                            })
                            .filter(id => id !== null);

                        // Filter selectedRows to only include IDs that exist in the new data
                        const updatedSelectedRows = currentSelectedRows.filter(id => 
                            validRowIds.includes(id)
                        );

                        // Update selectedRows state
                        setSelectedRows(updatedSelectedRows);
                    }
                }
            });
        } catch (error) {
            console.error("Error sending action to server:", error);
        }
    };

    // Function to get an icon component by name
    const getIconComponent = (iconName: string): IconComponent | undefined => {
        // First check if the icon is provided in the icons prop
        if (icons && icons[iconName]) {
            return icons[iconName];
        }

        // If not found in props, try to get it from Lucide icons
        const icon = getIconByName(iconName);
        return icon || undefined;
    };

    return (
        <div className="w-full space-y-4">
            <div className="flex items-center justify-between">
                <div className="flex flex-1 items-center space-x-2">
                    <Input
                        placeholder={t('search_placeholder')}
                        className="h-8 w-[150px] lg:w-[250px]"
                        onChange={(e) => handleDatatableAction('search', { search: e.target.value })}
                    />
                    {filters?.map(filter => {
                        const options = getFilterOptions(filter.name);
                        if (options.length === 0) return null;

                        return (
                            <DataTableFacetedFilter
                                key={filter.name}
                                title={filter.label}
                                options={options}
                                selectedValues={selectedFilterValues[filter.name] || new Set()}
                                onFilterChange={(values) => handleFilterChange(filter.name, values)}
                            />
                        );
                    })}
                    {Object.values(selectedFilterValues).some(set => set.size > 0) && (
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-8 px-2 lg:px-3"
                            onClick={handleResetFilters}
                        >
                            {t('reset')}
                            {(() => {
                                const XIcon = getIconComponent('X');
                                return XIcon ? <XIcon className="h-4 w-4" /> : null;
                            })()}
                        </Button>
                    )}
                </div>
                <div className="flex items-center space-x-2">
                    {/* Render action buttons */}
                    {actions && actions.length > 0 && (
                        <div className="flex items-center space-x-2">
                            {actions.map((action, index) => {
                                if (action.type === 'group') {
                                    // Render action group as dropdown
                                    return (
                                        <DropdownMenu key={index}>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className={`h-8 px-2 lg:px-3 ${action.styles || action.color || ''}`}
                                                    disabled={selectedRows.length === 0}
                                                    {...action.props}
                                                >
                                                    {action.icon && action.iconPosition !== 'right' && (
                                                        (() => {
                                                            const Icon = getIconComponent(action.icon);
                                                            return Icon ? <Icon className="h-4 w-4 mr-2" /> : null;
                                                        })()
                                                    )}
                                                    {action.label}
                                                    {action.icon && action.iconPosition === 'right' && (
                                                        (() => {
                                                            const Icon = getIconComponent(action.icon);
                                                            return Icon ? <Icon className="h-4 w-4 ml-2" /> : null;
                                                        })()
                                                    )}
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                {action.actions.map((subAction, subIndex) => (
                                                    <DropdownMenuItem
                                                        key={subIndex}
                                                        onClick={() => handleBulkAction(subAction.name)}
                                                        className={subAction.styles || subAction.color || ''}
                                                        {...subAction.props}
                                                    >
                                                        {subAction.icon && subAction.iconPosition !== 'right' && (
                                                            (() => {
                                                                const Icon = getIconComponent(subAction.icon);
                                                                return Icon ? <Icon className="h-4 w-4 mr-2" /> : null;
                                                            })()
                                                        )}
                                                        {subAction.label}
                                                        {subAction.icon && subAction.iconPosition === 'right' && (
                                                            (() => {
                                                                const Icon = getIconComponent(subAction.icon);
                                                                return Icon ? <Icon className="h-4 w-4 ml-2" /> : null;
                                                            })()
                                                        )}
                                                    </DropdownMenuItem>
                                                ))}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    );
                                } else {
                                    // Render single action as button
                                    return (
                                        <Button
                                            key={index}
                                            variant="outline"
                                            size="sm"
                                            className={`h-8 px-2 lg:px-3 ${action.styles || action.color || ''}`}
                                            onClick={() => handleBulkAction(action.name)}
                                            disabled={selectedRows.length === 0}
                                            {...action.props}
                                        >
                                            {action.icon && action.iconPosition !== 'right' && (
                                                (() => {
                                                    const Icon = getIconComponent(action.icon);
                                                    return Icon ? <Icon className="h-4 w-4 mr-2" /> : null;
                                                })()
                                            )}
                                            {action.label}
                                            {action.icon && action.iconPosition === 'right' && (
                                                (() => {
                                                    const Icon = getIconComponent(action.icon);
                                                    return Icon ? <Icon className="h-4 w-4 ml-2" /> : null;
                                                })()
                                            )}
                                        </Button>
                                    );
                                }
                            })}
                        </div>
                    )}
                    <DataTableViewOptions
                        columns={formattedColumns}
                        onToggleColumnVisibility={(columnKey, isVisible) => {
                            handleDatatableAction('toggleColumnVisibility', { columnKey, isVisible });
                        }}
                    />
                </div>
            </div>

            {/* Render table */}
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {/* Only show the select all checkbox if there's no checkbox column */}
                            {!columns.some(col => col.type === 'checkbox') && (
                                <TableHead className="w-[40px]">
                                    <Checkbox
                                        checked={selectedRows.length === data.total && data.total > 0}
                                        onCheckedChange={handleSelectAllRows}
                                    />
                                </TableHead>
                            )}
                            {formattedColumns.filter(column => column.isVisible).map((column) => (
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
                                            formattedData
                                                .filter(row => {
                                                    const value = row[`${column.key}_value`];
                                                    const isDisabled = row[`${column.key}_disabled`];
                                                    // Only include rows with valid values that aren't disabled
                                                    return value !== undefined && value !== null && !isDisabled;
                                                })
                                                .every(row => isRowSelected(row[`${column.key}_value`])) &&
                                            // Make sure there's at least one available checkbox
                                            formattedData.some(row => {
                                                const value = row[`${column.key}_value`];
                                                const isDisabled = row[`${column.key}_disabled`];
                                                return value !== undefined && value !== null && !isDisabled;
                                            })
                                            : selectedRows.length === data.total && data.total > 0}
                                        onCheckboxChange={handleSelectAllRows}
                                        onSort={(columnKey, sortDirection) => {
                                            if (!column.sortable) return;
                                            handleDatatableAction('sort', { sort: columnKey, direction: sortDirection });
                                        }}
                                        onHide={(columnKey) => {
                                            handleDatatableAction('toggleColumnVisibility', { columnKey, isVisible: false });
                                        }}
                                    />
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {formattedData.map((row, index) => (
                            <TableRow
                                key={row.id !== undefined && row.id !== null ? (typeof row.id === 'object' ? `row-obj-${index}` : String(row.id)) : `row-${index}`}
                                className={row.id !== undefined && row.id !== null && typeof row.id !== 'object' && isRowSelected(row.id as number | string) ? "bg-muted/50" : ""}>
                                {/* Only show the row checkbox if there's no checkbox column AND the row has a valid ID */}
                                {!columns.some(col => col.type === 'checkbox') && row.id !== undefined && row.id !== null && (
                                    <TableCell>
                                        <Checkbox
                                            checked={isRowSelected(row.id as number | string)}
                                            onCheckedChange={() => handleSelectRow(row.id as number | string)}
                                        />
                                    </TableCell>
                                )}
                                {/* Add an empty cell if there's no checkbox column AND no valid ID */}
                                {!columns.some(col => col.type === 'checkbox') && (row.id === undefined || row.id === null) && (
                                    <TableCell></TableCell>
                                )}
                                {formattedColumns.filter(column => column.isVisible).map((column) => {
                                    const columnKey = column.key;
                                    const iconName = row[`${columnKey}_icon`] as string | undefined;
                                    const IconComponent = iconName ? getIconComponent(iconName) : undefined;

                                    // Handle checkbox columns
                                    if (column.type === 'checkbox') {
                                        const value = row[`${columnKey}_value`];
                                        const isChecked = row[`${columnKey}_checked`] as boolean;
                                        const isDisabled = row[`${columnKey}_disabled`] as boolean;

                                        // Only show checkbox if value is valid
                                        if (value !== undefined && value !== null) {
                                            return (
                                                <TableCell key={columnKey}>
                                                    <Checkbox
                                                        checked={isRowSelected(value as number | string)}
                                                        disabled={isDisabled}
                                                        value={value as string}
                                                        onCheckedChange={() => handleSelectRow(value as number | string)}
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
                                                                {(() => {
                                                                    // Use the icon from the action group, or default to MoreHorizontal
                                                                    const iconName = actionData.icon || 'MoreHorizontal';
                                                                    const Icon = getIconComponent(iconName);
                                                                    return Icon ? <Icon className="h-4 w-4 text-current" /> : null;
                                                                })()}
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            {actionData.actions && actionData.actions.map((action, actionIndex) => {
                                                                                // Check if we need to add a separator after this action
                                                                                const showSeparator = action.separator;

                                                                                return (
                                                                                    <React.Fragment key={actionIndex}>
                                                                                        <DropdownMenuItem 
                                                                                            onClick={() => handleRowAction(action.name, row._id as number, action.url)}
                                                                                            className={action.styles || ''}
                                                                                            {...(action.props || {})}
                                                                                        >
                                                                            {action.icon && action.iconPosition !== 'right' && (
                                                                                (() => {
                                                                                    const Icon = getIconComponent(action.icon);
                                                                                    return Icon ? <Icon className="h-4 w-4 mr-2" /> : null;
                                                                                })()
                                                                            )}
                                                                            {action.label}
                                                                            {action.icon && action.iconPosition === 'right' && (
                                                                                (() => {
                                                                                    const Icon = getIconComponent(action.icon);
                                                                                    return Icon ? <Icon className="h-4 w-4 ml-2" /> : null;
                                                                                })()
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
                                                {IconComponent && <IconComponent className="h-4 w-4" />}
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

            {/* Pagination controls */}
            {data && (
                <DataTablePagination
                    pageSize={pageSize}
                    availablePageSizes={availablePageSizes}
                    onPageSizeChange={(newSize) => handleDatatableAction('pageSize', { pageSize: newSize })}
                    pagination={{
                        currentPage: data.current_page,
                        hasMorePages: data.current_page < data.last_page,
                        total: data.total,
                        prevPage: () => {
                            if (data.current_page > 1) {
                                handleDatatableAction('prevPage', { page: data.current_page - 1, filters: currentFilters });
                            }
                        },
                        nextPage: () => {
                            if (data.current_page < data.last_page) {
                                handleDatatableAction('nextPage', { page: data.current_page + 1, filters: currentFilters });
                            }
                        }
                    }}
                    onFirstPage={() => {
                        if (data.current_page !== 1) {
                            handleDatatableAction('firstPage', { page: 1, filters: currentFilters });
                        }
                    }}
                    onLastPage={() => {
                        if (data.current_page !== data.last_page) {
                            handleDatatableAction('lastPage', { page: data.last_page, filters: currentFilters });
                        }
                    }}
                />
            )}
            <Toaster />
        </div>
    );
};

export default Datatable;
