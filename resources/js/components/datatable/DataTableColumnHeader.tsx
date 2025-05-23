import {ArrowDown, ArrowUp, ChevronsUpDown, EyeOff} from "lucide-react"
import {useRef} from "react"

import {cn} from "../../lib/utils"
import {useTranslation} from "../../lib/useTranslation"
import {Button} from "../ui/button"
import {Checkbox} from "../ui/checkbox"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "../ui/dropdown-menu"

interface DataTableColumnHeaderProps extends React.HTMLAttributes<HTMLDivElement> {
    columnKey: string
    title?: string
    isSortable?: boolean
    isToggable?: boolean
    isCheckboxColumn?: boolean
    isAllChecked?: boolean
    sortKey?: string
    sortDirection?: string
    onSort?: (columnKey: string, direction: string) => void
    onHide?: (columnKey: string) => void
    onCheckboxChange?: () => void
}

export function DataTableColumnHeader({
                                          columnKey,
                                          title,
                                          className,
                                          isSortable = true,
                                          isToggable = true,
                                          isCheckboxColumn = false,
                                          isAllChecked = false,
                                          sortKey,
                                          sortDirection,
                                          onSort,
                                          onHide,
                                          onCheckboxChange,
                                      }: DataTableColumnHeaderProps) {
    const buttonRef = useRef<HTMLButtonElement>(null);
    const {t} = useTranslation();

    const isSorted = sortKey === columnKey;
    const isAsc = isSorted && sortDirection === 'asc';
    const isDesc = isSorted && sortDirection === 'desc';

    // If it's a checkbox column, render a simple div with a checkbox
    if (isCheckboxColumn) {
        return (
            <div
                className={cn("flex items-center space-x-2 cursor-pointer", className)}
                onClick={() => onCheckboxChange && onCheckboxChange()}
            >
                <Checkbox
                    checked={isAllChecked}
                    onCheckedChange={onCheckboxChange}
                    className="mr-2"
                />
            </div>
        );
    }

    // If neither sortable nor toggable, just show the title without dropdown
    if (!isSortable && !isToggable) {
        return (
            <div className={cn("flex items-center space-x-2", className)}>
                <span className="text-xs">{title}</span>
            </div>
        );
    }
    if (title) {
        return (
            <div className={cn("flex items-center space-x-2", className)}>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            ref={buttonRef}
                            variant="ghost"
                            size="sm"
                            className="-ml-3 h-8 data-[state=open]:bg-accent text-xs outline-none"
                            onClick={(e) => {
                                // Blur the button after click to remove focus
                                e.currentTarget.blur();
                            }}
                        >
                            <span>{title}</span>
                            {isSortable && (
                                <>{
                                    isDesc ? (
                                        <ArrowDown className="ml-2 h-4 w-4 text-current"/>
                                    ) : isAsc ? (
                                        <ArrowUp className="ml-2 h-4 w-4 text-current"/>
                                    ) : (
                                        <ChevronsUpDown className="ml-2 h-4 w-4 text-current"/>
                                    )
                                }</>
                            )}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        {isSortable && (
                            <>
                                <DropdownMenuItem
                                    onClick={() => {
                                        if (onSort) {
                                            onSort(columnKey, 'asc');
                                            if (buttonRef.current) {
                                                buttonRef.current.blur();
                                            }
                                        }
                                    }}
                                    disabled={isAsc}
                                >
                                    <ArrowUp className="mr-2 h-3.5 w-3.5 text-muted-foreground/70"/>
                                    {t('sort_ascending')}
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onClick={() => {
                                        if (onSort) {
                                            onSort(columnKey, 'desc');
                                            if (buttonRef.current) {
                                                buttonRef.current.blur();
                                            }
                                        }
                                    }}
                                    disabled={isDesc}
                                >
                                    <ArrowDown className="mr-2 h-3.5 w-3.5 text-muted-foreground/70"/>
                                    {t('sort_descending')}
                                </DropdownMenuItem>
                                {isToggable && <DropdownMenuSeparator/>}
                            </>
                        )}
                        {isToggable && (
                            <DropdownMenuItem
                                onClick={() => {
                                    if (onHide) {
                                        onHide(columnKey);
                                        // Blur the button after hiding to remove focus
                                        if (buttonRef.current) {
                                            buttonRef.current.blur();
                                        }
                                    }
                                }}
                            >
                                <EyeOff className="mr-2 h-3.5 w-3.5 text-muted-foreground/70"/>
                                {t('hide')}
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        )
    }
}