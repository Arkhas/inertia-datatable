import { ReactNode } from 'react';

export interface Column {
  key: string;
  name: string;
  label: string;
  isVisible: boolean;
  hasIcon?: boolean;
  type?: string;
  sortable?: boolean;
  filterable?: boolean;
  toggable?: boolean;
  width?: string;
}

export interface TableAction {
  type: 'action';
  name: string;
  label: string;
  styles?: string;
  icon?: string;
  iconPosition?: string;
  props?: Record<string, any>;
  hasConfirmCallback?: boolean;
  separator?: boolean;
  url?: string;
}

export interface TableActionGroup {
  type: 'group';
  name: string;
  label: string;
  styles?: string;
  icon?: string;
  iconPosition?: string;
  props?: Record<string, any>;
  actions: TableAction[];
}

export interface FilterOption {
  value: string;
  label: string;
  icon?: string;
  iconPosition?: string;
  count?: number;
}

export interface FilterDefinition {
  name: string;
  label: string;
  options: Record<string, string>;
  icons?: Record<string, string>;
  multiple: boolean;
  filterOptions?: FilterOption[];
}

export interface ConfirmDialogContent {
  title: string;
  message: string;
  confirm: string;
  cancel: string;
  disabled: boolean;
}

export interface PendingAction {
  actionName: string;
  ids: (number | string)[];
  url?: string;
}

export interface FormattedData {
  [key: string]: ReactNode | string | number | boolean | null;
  _id?: number | string;
  id?: number | string;
}

export interface Pagination {
  currentPage: number;
  hasMorePages: boolean;
  total: number;
  prevPage: () => void;
  nextPage: () => void;
}

export interface PageProps {
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
    confirmData?: {
      title?: string;
      message?: string;
      confirm?: string;
      cancel?: string;
      disabled?: boolean;
    };
  };
  visibleColumns?: Record<string, boolean>;
  exportable?: boolean;
  exportType?: string;
  exportColumn?: string;
}

export interface DatatableProps {
  route?: string;
  name: string;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}
