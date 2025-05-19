import { DropdownMenuTrigger } from "@radix-ui/react-dropdown-menu"
import { Settings2, SearchIcon } from "lucide-react"

import { useTranslation } from "../../lib/useTranslation"
import { Button } from "./button"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from "./dropdown-menu"
import { useState, useRef, useEffect } from "react"

interface DataTableViewOptionsProps {
  columns: { key: string; label: string; isVisible?: boolean; toggable?: boolean }[]
  onToggleColumnVisibility: (columnKey: string, isVisible: boolean) => void
}

export function DataTableViewOptions({
  columns,
  onToggleColumnVisibility,
}: DataTableViewOptionsProps) {
  const [searchQuery, setSearchQuery] = useState("");
  const searchInputRef = useRef<HTMLInputElement>(null);
  const [isOpen, setIsOpen] = useState(false);
  const { t } = useTranslation();

  const filteredColumns = columns.filter((column) =>
    column.label.toLowerCase().includes(searchQuery.toLowerCase()) && 
    column.toggable !== false
  );

  // Restore focus to search input when dropdown content re-renders
  useEffect(() => {
    if (isOpen && searchInputRef.current) {
      // Use a small timeout to ensure the DOM has updated
      const timeoutId = setTimeout(() => {
        searchInputRef.current?.focus();
      }, 0);
      return () => clearTimeout(timeoutId);
    }
  }, [searchQuery, isOpen]);

  const handleCheckboxClick = (e: React.MouseEvent, columnKey: string, isVisible: boolean) => {
    // Prevent the event from propagating to parent elements
    e.preventDefault();
    e.stopPropagation();

    // Use setTimeout to ensure the toggle happens after the event has been fully processed
    setTimeout(() => {
      onToggleColumnVisibility(columnKey, isVisible);
    }, 0);
  };

  return (
    <DropdownMenu modal={true} closeOnSelect={false} open={isOpen} onOpenChange={setIsOpen}>
      <DropdownMenuTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className="ml-auto h-8 lg:flex"
        >
          <Settings2 className="h-4 w-4 text-current" />
          <span className={"ml-2 lg:block hidden"}>{t('columns')}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        align="end"
        className="w-[250px]"
      >
        <div className="px-2 py-2">
          <div className="flex items-center border rounded-md px-2">
            <SearchIcon className="h-4 w-4 text-muted-foreground mr-2" />
            <input
              ref={searchInputRef}
              className="flex h-8 w-full bg-transparent py-2 text-sm outline-none placeholder:text-muted-foreground"
              placeholder={t('search_columns_placeholder')}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onClick={(e) => e.stopPropagation()}
            />
          </div>
        </div>
        <div className="max-h-[300px] overflow-y-auto">
          {filteredColumns.map((column) => {
            const isChecked = column.isVisible !== false;
            return (
              <DropdownMenuCheckboxItem
                key={column.key}
                className="capitalize"
                checked={isChecked}
                onSelect={(e) => {
                  e.preventDefault(); // Prevent default selection behavior
                }}
                onClick={(e) => {
                  // Capture the click event and prevent it from closing the dropdown
                  handleCheckboxClick(e, column.key, !isChecked);
                  // Return false to prevent the default action
                  return false;
                }}
              >
                {column.label}
              </DropdownMenuCheckboxItem>
            )
          })}
          {filteredColumns.length === 0 && (
            <div className="text-center py-2 text-sm text-muted-foreground">
              {t('no_columns_found')}
            </div>
          )}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
