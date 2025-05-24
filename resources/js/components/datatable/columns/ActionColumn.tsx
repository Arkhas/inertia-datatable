import React from 'react';
import { TableCell } from "../../ui/table";
import { Button } from "../../ui/button";
import { IconRenderer } from '../IconRenderer';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../../ui/dropdown-menu";

interface ActionColumnProps {
  columnKey: string;
  row: Record<string, React.ReactNode | string | number | boolean | null>;
  onRowAction: (action: string, rowId: number | string, url?: string, columnKey?: string) => void;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
  t?: (key: string) => string;
}

export const ActionColumn: React.FC<ActionColumnProps> = ({
  columnKey,
  row,
  onRowAction,
  icons = {},
  t = (key: string) => key
}) => {
  const actionData = row[`${columnKey}_action`];

  if (!actionData || !actionData.actions) {
    return <TableCell className="p-0"></TableCell>;
  }

  // If there's only one action, render a single button
  if (actionData.actions.length === 1) {
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
            className={action.styles}
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
  }

  // For multiple actions, render a dropdown
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
          {actionData.actions.map((action, actionIndex) => {
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
