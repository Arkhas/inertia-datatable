import * as React from "react"
import { PlusCircle } from "lucide-react"

import { cn } from "../../lib/utils"
import { Checkbox } from "../ui/checkbox"
import { RadioGroup, RadioGroupItem } from "../ui/radio-group"
import { useTranslation } from "../../lib/useTranslation"
import { Badge } from "../ui/badge"
import { Button } from "../ui/button"
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from "../ui/command"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "../ui/popover"
import { Separator } from "../ui/separator"

interface DataTableFacetedFilterProps {
  title?: string
  options: {
    label: string
    value: string
    icon?: React.ComponentType<{ className?: string }>
    count?: number
  }[]
  selectedValues: Set<string>
  onFilterChange: (values: string[]) => void
  multiple?: boolean
}

export function DataTableFacetedFilter({
  title,
  options,
  selectedValues,
  onFilterChange,
  multiple = true,
}: DataTableFacetedFilterProps) {
  const { t } = useTranslation();

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="outline" size="sm" className="h-8 border-dashed mr-2">
          <PlusCircle className="mr-2 h-4 w-4 text-current" />
          {title}
          {selectedValues?.size > 0 && (
            <>
              <Separator orientation="vertical" className="mx-2 h-4" />
              <Badge
                variant="secondary"
                className="rounded-sm px-1 font-normal lg:hidden"
              >
                {selectedValues.size}
              </Badge>
              <div className="hidden space-x-1 lg:flex">
                {selectedValues.size > 2 ? (
                  <Badge
                    variant="secondary"
                    className="rounded-sm px-1 font-normal"
                  >
                    {t('filter_selected', { count: selectedValues.size })}
                  </Badge>
                ) : (
                  options
                    .filter((option) => selectedValues.has(option.value))
                    .map((option) => (
                      <Badge
                        variant="secondary"
                        key={option.value}
                        className="rounded-sm px-1 font-normal"
                      >
                        {option.label}
                      </Badge>
                    ))
                )}
              </div>
            </>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[200px] p-0" align="start">
        <Command>
          <CommandInput placeholder={title} />
          <CommandList>
            <CommandEmpty>{t('no_results_found')}</CommandEmpty>
            <CommandGroup>
              {multiple ? (
                // Render checkboxes for multiple selection
                options.map((option) => {
                  const isSelected = selectedValues.has(option.value)
                  return (
                    <CommandItem
                      key={option.value}
                      onSelect={() => {}}
                    >
                      <Checkbox
                        checked={isSelected}
                        className="mr-2 text-white"
                        onCheckedChange={() => {
                          const newSelectedValues = new Set(selectedValues);
                          if (isSelected) {
                            newSelectedValues.delete(option.value);
                          } else {
                            newSelectedValues.add(option.value);
                          }
                          const filterValues = Array.from(newSelectedValues);
                          onFilterChange(filterValues);
                        }}
                      />
                      {option.icon && (
                        <option.icon className="mr-2 h-4 w-4" />
                      )}
                      <span>{option.label}</span>
                      {option.count !== undefined && (
                        <span className="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                          {option.count}
                        </span>
                      )}
                    </CommandItem>
                  )
                })
              ) : (
                // Render radio buttons for single selection
                <RadioGroup
                  value={selectedValues.size > 0 ? Array.from(selectedValues)[0] : undefined}
                  onValueChange={(value) => {
                    onFilterChange([value]);
                  }}
                >
                  {options.map((option) => {
                    const isSelected = selectedValues.has(option.value)
                    return (
                      <CommandItem
                        key={option.value}
                        onSelect={() => {
                          const newValue = option.value;
                          onFilterChange([newValue]);
                        }}
                      >
                        <div className="flex items-center">
                          <RadioGroupItem
                            value={option.value}
                            className="mr-2 text-white"
                          />
                          {option.icon && (
                            <option.icon className="mr-2 h-4 w-4" />
                          )}
                          <span>{option.label}</span>
                          {option.count !== undefined && (
                            <span className="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                              {option.count}
                            </span>
                          )}
                        </div>
                      </CommandItem>
                    )
                  })}
                </RadioGroup>
              )}
            </CommandGroup>
            {selectedValues.size > 0 && (
              <>
                <CommandSeparator />
                <CommandGroup>
                  <CommandItem
                    onSelect={() => onFilterChange([])}
                    className="justify-center text-center"
                  >
                    {t('clear_filters')}
                  </CommandItem>
                </CommandGroup>
              </>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}