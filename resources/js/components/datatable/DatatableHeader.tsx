import React from 'react';
import { Input } from "../ui/input";
import { DataTableViewOptions } from "./DataTableViewOptions";
import { DatatableFilters } from './DatatableFilters';
import { DatatableActions } from './DatatableActions';
import {Column, TableAction, TableActionGroup, FilterDefinition} from "./types";

interface DatatableHeaderProps {
  columns: Column[];
  filters?: FilterDefinition[];
  actions?: (TableAction | TableActionGroup)[];
  selectedFilterValues: Record<string, Set<string>>;
  selectedRows: (number | string)[];
  onSearch: (search: string) => void;
  onFilterChange: (filterName: string, values: string[]) => void;
  onResetFilters: () => void;
  onActionClick: (actionName: string) => void;
  onToggleColumnVisibility: (columnKey: string, isVisible: boolean) => void;
  t: (key: string) => string;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const DatatableHeader: React.FC<DatatableHeaderProps> = ({
  columns,
  filters,
  actions,
  selectedFilterValues,
  selectedRows,
  onSearch,
  onFilterChange,
  onResetFilters,
  onActionClick,
  onToggleColumnVisibility,
  t,
  icons = {}
}) => {
  return (
    <>
      {/* Mobile: search bar above controls */}
      <div className="block lg:hidden mb-2">
        <Input
          placeholder={t('search_placeholder')}
          className="h-8 w-full"
          onChange={(e) => onSearch(e.target.value)}
        />
      </div>

      {/* Mobile: filters and reset button below search bar */}
      <div className="lg:hidden">
        <DatatableFilters
          filters={filters || []}
          selectedFilterValues={selectedFilterValues}
          onFilterChange={onFilterChange}
          onResetFilters={onResetFilters}
          t={t}
          isMobile={true}
          icons={icons}
        />
      </div>

      <div className="flex items-center justify-between">
        {/* Desktop: search bar inline with controls */}
        <div className="hidden lg:flex flex-1 items-center space-x-2">
          <Input
            placeholder={t('search_placeholder')}
            className="h-8 w-[150px] lg:w-[250px]"
            onChange={(e) => onSearch(e.target.value)}
          />
          <DatatableFilters
            filters={filters || []}
            selectedFilterValues={selectedFilterValues}
            onFilterChange={onFilterChange}
            onResetFilters={onResetFilters}
            t={t}
            icons={icons}
          />
        </div>

        <div className="flex items-center space-x-2">
          {/* Render action buttons */}
          {actions && actions.length > 0 && (
            <DatatableActions
              actions={actions}
              selectedRows={selectedRows}
              onActionClick={onActionClick}
              icons={icons}
            />
          )}

          <DataTableViewOptions
            columns={columns}
            onToggleColumnVisibility={(columnKey, isVisible) => {
              onToggleColumnVisibility(columnKey, isVisible);
            }}
          />
        </div>
      </div>
    </>
  );
};
