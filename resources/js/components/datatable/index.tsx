import React, { useState, useEffect, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTranslation, updateTranslations } from '../../lib/useTranslation';
import { Toaster, toast } from 'sonner';
import { DataTablePagination } from "../ui/data-table-pagination";
import { DatatableHeader } from './DatatableHeader';
import { DatatableBody } from './DatatableBody';
import { DatatableConfirmDialog } from './DatatableConfirmDialog';
import { getIconComponent } from './IconRenderer';
import {
  DatatableProps,
  PageProps,
  FormattedColumn,
  FormattedData,
  PendingAction,
  ConfirmDialogContent
} from './types';

const Datatable: React.FC<DatatableProps> = ({ route: routeName, icons = {} }) => {
  // Get data from Inertia page props
  const page = usePage();
  const { columns, filters, actions, currentFilters, data, pageSize, availablePageSizes, sort, direction, translations, actionResult, visibleColumns: propsVisibleColumns } = page.props as PageProps;

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

  // State for confirmation dialog
  const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
  const [confirmDialogContent, setConfirmDialogContent] = useState<ConfirmDialogContent>({
    title: '',
    message: '',
    confirm: '',
    cancel: '',
    disabled: false
  });
  const [pendingAction, setPendingAction] = useState<PendingAction | null>(null);

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
    const row: FormattedData = {};

    // Store the original ID value from the server
    if (item.id !== undefined) {
      row._id = item.id;
    }

    columns.forEach(column => {
      const columnName = column.name;
      if (item[`${columnName}_html`]) {
        row[columnName] = <div dangerouslySetInnerHTML={{ __html: item[`${columnName}_html`] as string }} />;
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
      // If visibleColumns is provided in props, use it
      if (propsVisibleColumns) {
        setVisibleColumns(propsVisibleColumns as Record<string, boolean>);
      } else {
        // Otherwise, initialize all columns as visible
        const initialVisibleColumns: Record<string, boolean> = {};
        columns.forEach(column => {
          initialVisibleColumns[column.name] = true;
        });
        setVisibleColumns(initialVisibleColumns);
      }
    }
  }, [columns, propsVisibleColumns]);

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
    if (params.search !== undefined && typeof params.search === 'string') {
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
        // Update local state
        const updatedVisibleColumns = {
          ...visibleColumns,
          [params.columnKey as string]: params.isVisible as boolean
        };
        setVisibleColumns(updatedVisibleColumns);

        // Send to server to store in session
        const url = route(routeName, {});
        router.post(url, {
          visibleColumns: updatedVisibleColumns
        }, {
          preserveState: true,
          preserveScroll: true,
          only: ['visibleColumns']
        });
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
        only: ['data', 'currentFilters', 'sort', 'direction', 'pageSize', 'visibleColumns'],
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
    handleDatatableAction('filter', { filters: {} });
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

  // Execute the pending action
  const executePendingAction = (): void => {
    if (!pendingAction) {
      return;
    }

    const { actionName, ids, url } = pendingAction;

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

      // Set actionTriggered to true to indicate that an action was explicitly triggered
      setActionTriggered(true);

      // Store current selectedRows before making the request
      const currentSelectedRows = [...selectedRows];

      router.post(actionUrl, {
        action: actionName,
        ids: ids
      }, {
        preserveState: true,
        preserveScroll: true,
        only: ['data', 'actionResult', 'visibleColumns'],
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

  // Handle row actions
  const handleRowAction = (action: string, taskId: number | string, url?: string, columnKey?: string): void => {
    // Find the action in the row data
    let actionData;

    if (columnKey) {
      // If columnKey is provided, find the action in the column's actions array
      const row = formattedData.find(row => row._id === taskId);
      if (row) {
        const columnActionData = row[`${columnKey}_action`];
        if (columnActionData && columnActionData.actions) {
          actionData = columnActionData.actions.find(a => a.name === action);
        }
      }
    } else {
      // Fallback to the old behavior
      actionData = formattedData.find(row => row._id === taskId)?.[`${action}_action`];
    }

    // Check if the action has a confirmation callback
    if (actionData && actionData.hasConfirmCallback) {
      // Set the pending action
      setPendingAction({
        actionName: action,
        ids: [taskId],
        url
      });

      // Get the confirmation data from the server
      try {
        const actionUrl = route(routeName);
        router.post(actionUrl, {
          action: `${action}_confirm`,
          ids: [taskId]
        }, {
          preserveState: true,
          preserveScroll: true,
          only: ['actionResult'],
          onSuccess: (page) => {
            const { actionResult } = page.props as any;

            if (actionResult.confirmData) {
              // Set the confirmation dialog content
              setConfirmDialogContent({
                title: actionResult.confirmData.title || 'Are you sure?',
                message: actionResult.confirmData.message || 'This action cannot be undone.',
                confirm: actionResult.confirmData.confirm || 'Confirm',
                cancel: actionResult.confirmData.cancel || 'Cancel',
                disabled: actionResult.confirmData.disabled || false
              });
              // Open the confirmation dialog
              setConfirmDialogOpen(true);
            }
          }
        });
      } catch (error) {
        console.error("Error getting confirmation data:", error);
      }
    } else {
      // If no confirmation is needed, execute the action directly
      setPendingAction({
        actionName: action,
        ids: [taskId],
        url
      });
      executePendingAction();
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

    // Find the action in the actions array
    const action = actions?.find(a => {
      if (a.type === 'action') {
        return a.name === actionName;
      } else if (a.type === 'group') {
        return a.actions.some(sa => sa.name === actionName);
      }
      return false;
    });

    // Get the specific action if it's in a group
    const specificAction = action?.type === 'group' 
      ? action.actions.find(a => a.name === actionName) 
      : action?.type === 'action' ? action : null;

    // Check if the action has a confirmation callback
    if (specificAction && specificAction.hasConfirmCallback) {
      // Set the pending action
      setPendingAction({
        actionName,
        ids: selectedIds
      });

      // Get the confirmation data from the server
      try {
        const actionUrl = route(routeName);
        router.post(actionUrl, {
          action: `${actionName}_confirm`,
          ids: selectedIds
        }, {
          preserveState: true,
          preserveScroll: true,
          only: ['actionResult'],
          onSuccess: (page) => {
            const { actionResult } = page.props as any;

            if (actionResult.confirmData) {
              // Set the confirmation dialog content
              setConfirmDialogContent({
                title: actionResult.confirmData.title || 'Are you sure?',
                message: actionResult.confirmData.message || 'This action cannot be undone.',
                confirm: actionResult.confirmData.confirm || 'Confirm',
                cancel: actionResult.confirmData.cancel || 'Cancel',
                disabled: actionResult.confirmData.disabled || false
              });
              // Open the confirmation dialog
              setConfirmDialogOpen(true);
            }
          }
        });
      } catch (error) {
        console.error("Error getting confirmation data:", error);
      }
    } else {
      // If no confirmation is needed, execute the action directly
      setPendingAction({
        actionName,
        ids: selectedIds
      });
      executePendingAction();
    }
  };

  return (
    <div className="w-full space-y-4">
      {/* Header with search, filters, and actions */}
      <DatatableHeader
        columns={formattedColumns}
        filters={filters}
        actions={actions}
        selectedFilterValues={selectedFilterValues}
        selectedRows={selectedRows}
        onSearch={(search) => handleDatatableAction('search', { search })}
        onFilterChange={handleFilterChange}
        onResetFilters={handleResetFilters}
        onActionClick={handleBulkAction}
        onToggleColumnVisibility={(columnKey, isVisible) => {
          handleDatatableAction('toggleColumnVisibility', { columnKey, isVisible });
        }}
        t={t}
        icons={icons}
      />

      {/* Table body with rows and cells */}
      <DatatableBody
        columns={formattedColumns}
        data={formattedData}
        selectedRows={selectedRows}
        sort={sort}
        direction={direction}
        total={data?.total || 0}
        onSort={(columnKey, sortDirection) => {
          handleDatatableAction('sort', { sort: columnKey, direction: sortDirection });
        }}
        onToggleColumnVisibility={(columnKey, isVisible) => {
          handleDatatableAction('toggleColumnVisibility', { columnKey, isVisible });
        }}
        onSelectRow={handleSelectRow}
        onSelectAllRows={handleSelectAllRows}
        onRowAction={handleRowAction}
        isRowSelected={isRowSelected}
        t={t}
        icons={icons}
      />

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

      {/* Confirmation Dialog */}
      <DatatableConfirmDialog
        open={confirmDialogOpen}
        onOpenChange={setConfirmDialogOpen}
        content={confirmDialogContent}
        onConfirm={() => {
          executePendingAction();
          setConfirmDialogOpen(false);
        }}
        onCancel={() => {
          setPendingAction(null);
          setConfirmDialogOpen(false);
        }}
      />
    </div>
  );
};

export default Datatable;
