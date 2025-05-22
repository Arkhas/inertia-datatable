import React from 'react';
import { Button } from "../ui/button";
import { DataTableFacetedFilter } from "../ui/data-table-faceted-filter";
import { IconRenderer } from './IconRenderer';
import { FilterOption, FilterDefinition } from './types';

interface DatatableFiltersProps {
  filters: FilterDefinition[];
  selectedFilterValues: Record<string, Set<string>>;
  onFilterChange: (filterName: string, values: string[]) => void;
  onResetFilters: () => void;
  t: (key: string) => string;
  isMobile?: boolean;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const DatatableFilters: React.FC<DatatableFiltersProps> = ({
  filters,
  selectedFilterValues,
  onFilterChange,
  onResetFilters,
  t,
  isMobile = false,
  icons = {}
}) => {
  // Map filter options to the format expected by DataTableFacetedFilter
  const getFilterOptions = (filterName: string) => {
    const filter = filters?.find(f => f.name === filterName);
    if (!filter || !filter.filterOptions || !Array.isArray(filter.filterOptions)) return [];

    return filter.filterOptions.map(option => ({
      label: option.label,
      value: option.value,
      icon: option.icon ? <IconRenderer iconName={option.icon} icons={icons} /> : undefined,
      count: option.count
    }));
  };

  const hasActiveFilters = Object.values(selectedFilterValues).some(set => set.size > 0);

  return (
    <div className={isMobile ? "flex flex-row gap-2 mb-2" : "flex items-center space-x-2"}>
      <div className={isMobile ? "flex flex-wrap gap-2 flex-1" : ""}>
        {filters?.map(filter => {
          const options = getFilterOptions(filter.name);
          if (options.length === 0) return null;
          return (
            <DataTableFacetedFilter
              key={filter.name}
              title={filter.label}
              options={options}
              selectedValues={selectedFilterValues[filter.name] || new Set()}
              onFilterChange={(values) => onFilterChange(filter.name, values)}
              multiple={filter.multiple}
            />
          );
        })}
      </div>
      {hasActiveFilters && (
        <div className={isMobile ? "flex items-center justify-end flex-shrink-0 w-auto" : ""}>
          <Button
            variant="outline"
            size="sm"
            className={isMobile ? "h-8 px-2" : "h-8 px-2 lg:px-3 mr-2"}
            onClick={onResetFilters}
          >
            {!isMobile && <span className="lg:block hidden">{t('reset')}</span>}
            <IconRenderer iconName="X" icons={icons} />
          </Button>
        </div>
      )}
    </div>
  );
};
