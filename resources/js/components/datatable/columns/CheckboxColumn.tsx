import React from 'react';
import { TableCell } from "../../ui/table";
import { Checkbox } from "../../ui/checkbox";

interface CheckboxColumnProps {
  columnKey: string;
  row: Record<string, React.ReactNode | string | number | boolean | null>;
  isRowSelected: (rowId: number | string) => boolean;
  onSelectRow: (rowId: number | string) => void;
}

export const CheckboxColumn: React.FC<CheckboxColumnProps> = ({ 
  columnKey, 
  row, 
  isRowSelected, 
  onSelectRow 
}) => {
  const value = row[`${columnKey}_value`];
  const isDisabled = row[`${columnKey}_disabled`] as boolean;

  return (
    <TableCell>
      {value !== undefined && value !== null && (
        <Checkbox
          className="aligne-text-top"
          checked={isRowSelected(value as number | string)}
          disabled={isDisabled}
          value={value as string}
          onCheckedChange={() => onSelectRow(value as number | string)}
        />
      )}
    </TableCell>
  );
};