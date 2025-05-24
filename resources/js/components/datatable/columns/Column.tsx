import React from 'react';
import { TableCell } from "../../ui/table";
import { IconRenderer } from '../IconRenderer';

interface ColumnProps {
  columnKey: string;
  row: Record<string, React.ReactNode | string | number | boolean | null>;
  column: {
    key: string;
    width?: string;
  };
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const Column: React.FC<ColumnProps> = ({ columnKey, row, column, icons = {} }) => {
  const iconName = row[`${columnKey}_icon`] as string | undefined;

  return (
    <TableCell key={columnKey}>
      <div
        className="flex items-center gap-2 text-ellipsis truncate"
        style={column.width ? {width: column.width} : {}}
      >
        {iconName && <IconRenderer iconName={iconName} className="h-4 w-4" icons={icons} />}
        {row[columnKey]}
      </div>
    </TableCell>
  );
};