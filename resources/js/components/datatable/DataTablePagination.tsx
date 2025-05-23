import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from "lucide-react"

import { useTranslation } from "../../lib/useTranslation"
import { Button } from "../ui/button"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../ui/select"

interface DataTablePaginationProps {
  pageSize: number;
  availablePageSizes: number[];
  onPageSizeChange: (pageSize: number) => void;
  pagination: {
    currentPage: number;
    hasMorePages: boolean;
    total: number;
    prevPage: () => void;
    nextPage: () => void;
  };
  onFirstPage: () => void;
  onLastPage: () => void;
}

export function DataTablePagination({
  pageSize,
  availablePageSizes,
  onPageSizeChange,
  pagination,
  onFirstPage,
  onLastPage,
}: DataTablePaginationProps) {
  const pageCount = Math.ceil(pagination.total / pageSize);
  const { t } = useTranslation();

  return (
    <div className="flex items-center justify-between px-2">
      <div className="flex-1 text-sm text-muted-foreground">
        {t('pagination_showing')} {((pagination.currentPage - 1) * pageSize) + 1} {t('pagination_to')} {Math.min(pagination.currentPage * pageSize, pagination.total)} {t('pagination_of')} {pagination.total} {t('pagination_results')}
      </div>
      <div className="flex items-center space-x-6 lg:space-x-8">
        <div className="flex items-center space-x-2">
          <p className="text-sm font-medium hidden lg:block">{t('rows_per_page')}</p>
          <Select
            value={`${pageSize}`}
            onValueChange={(value) => {
              onPageSizeChange(Number(value))
            }}
          >
            <SelectTrigger className="h-8 w-[70px]">
              <SelectValue>{pageSize}</SelectValue>
            </SelectTrigger>
            <SelectContent side="top">
              {availablePageSizes.map((size) => (
                <SelectItem key={size} value={`${size}`}>
                  {size}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex w-[100px] items-center justify-center text-sm font-medium">
          {t('page_info', { current: pagination.currentPage, total: pageCount })}
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            className="hidden h-8 w-8 p-0 lg:flex"
            onClick={onFirstPage}
            disabled={pagination.currentPage === 1}
          >
            <span className="sr-only">{t('go_to_first_page')}</span>
            <ChevronsLeft className="h-4 w-4 text-current" />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={pagination.prevPage}
            disabled={pagination.currentPage === 1}
          >
            <span className="sr-only">{t('go_to_previous_page')}</span>
            <ChevronLeft className="h-4 w-4 text-current" />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={pagination.nextPage}
            disabled={!pagination.hasMorePages}
          >
            <span className="sr-only">{t('go_to_next_page')}</span>
            <ChevronRight className="h-4 w-4 text-current" />
          </Button>
          <Button
            variant="outline"
            className="hidden h-8 w-8 p-0 lg:flex"
            onClick={onLastPage}
            disabled={!pagination.hasMorePages}
          >
            <span className="sr-only">{t('go_to_last_page')}</span>
            <ChevronsRight className="h-4 w-4 text-current" />
          </Button>
        </div>
      </div>
    </div>
  )
}