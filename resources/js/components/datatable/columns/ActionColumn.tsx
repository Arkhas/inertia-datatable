import React from 'react';
import { TableCell } from "../../ui/table";
import { Button } from "../../ui/button";
import { IconRenderer } from '../IconRenderer';

interface ActionColumnProps {
  columnKey: string;
  row: Record<string, React.ReactNode | string | number | boolean | null>;
  onRowAction: (action: string, rowId: number | string, url?: string, columnKey?: string) => void;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const ActionColumn: React.FC<ActionColumnProps> = ({ 
  columnKey, 
  row, 
  onRowAction, 
  icons = {} 
}) => {
  const actionData = row[`${columnKey}_action`];
  if (!actionData || !actionData.actions || actionData.actions.length !== 1) {
    return <TableCell className="p-0"></TableCell>;
  }

  const action = actionData.actions[0];

  return (
    <TableCell className="p-0">
      {action.url ? (
        <Button 
          variant="ghost" 
          size="sm" 
          className={action.styles || ''}
          asChild
          {...(action.props || {})}
        >
          <a href={action.url}>
            {action.icon && action.iconPosition !== 'right' && (
              <IconRenderer
                iconName={action.icon}
                className="h-4 w-4 mr-2"
                icons={icons}
              />
            )}
            {action.label}
            {action.icon && action.iconPosition === 'right' && (
              <IconRenderer
                iconName={action.icon}
                className="h-4 w-4 ml-2"
                icons={icons}
              />
            )}
          </a>
        </Button>
      ) : (
        <Button 
          variant="ghost" 
          size="sm" 
          className={action.styles || ''}
          onClick={() => onRowAction(action.name, row._id as number | string, action.url, columnKey)}
          {...(action.props || {})}
        >
          {action.icon && action.iconPosition !== 'right' && (
            <IconRenderer
              iconName={action.icon}
              className="h-4 w-4"
              icons={icons}
            />
          )}
          {action.label && (
            <span className="mx-2">{action.label}</span>
          )}
          {action.icon && action.iconPosition === 'right' && (
            <IconRenderer
              iconName={action.icon}
              className="h-4 w-4"
              icons={icons}
            />
          )}
        </Button>
      )}
    </TableCell>
  );
};