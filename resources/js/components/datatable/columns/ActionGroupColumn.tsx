import React from 'react';
import { TableCell } from "../../ui/table";
import { IconRenderer } from '../IconRenderer';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../../ui/dropdown-menu";
import { Button } from "../../ui/button";

interface ActionGroupColumnProps {
  columnKey: string;
  row: Record<string, React.ReactNode | string | number | boolean | null>;
  onRowAction: (action: string, rowId: number | string, url?: string, columnKey?: string) => void;
  t: (key: string) => string;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const ActionGroupColumn: React.FC<ActionGroupColumnProps> = ({ 
  columnKey, 
  row, 
  onRowAction, 
  t,
  icons = {} 
}) => {
  const actionData = row[`${columnKey}_action`];
  if (!actionData) {
    return <TableCell className="p-0"></TableCell>;
  }

  // If there's only one action, use the ActionColumn component instead
  if (actionData.actions && actionData.actions.length === 1) {
    return <TableCell className="p-0"></TableCell>;
  }

  return (
    <TableCell className="p-0">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
            <IconRenderer
              iconName={actionData.icon || 'MoreHorizontal'}
              className="h-4 w-4 text-current"
              icons={icons}
            />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          {actionData.actions && actionData.actions.map((action, actionIndex) => {
            // Check if we need to add a separator after this action
            const showSeparator = action.separator;

            return (
              <React.Fragment key={actionIndex}>
                {action.url ? (
                  <DropdownMenuItem asChild>
                    <a
                      href={action.url}
                      className={action.styles || ''}
                      {...(action.props || {})}
                    >
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
                  </DropdownMenuItem>
                ) : (
                  <DropdownMenuItem
                    onClick={() => onRowAction(action.name, row._id as number | string, action.url, columnKey)}
                    className={action.styles || ''}
                    {...(action.props || {})}
                  >
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
                  </DropdownMenuItem>
                )}
                {showSeparator && <DropdownMenuSeparator/>}
              </React.Fragment>
            );
          })}
          {(!actionData.actions || actionData.actions.length === 0) && (
            <DropdownMenuItem disabled>{t('no_actions_available')}</DropdownMenuItem>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </TableCell>
  );
};