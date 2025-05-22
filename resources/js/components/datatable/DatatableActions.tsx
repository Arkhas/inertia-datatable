import React from 'react';
import { Button } from "../ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "../ui/dropdown-menu";
import { IconRenderer } from './IconRenderer';

interface TableAction {
  type: 'action';
  name: string;
  label: string;
  styles?: string;
  icon?: string;
  iconPosition?: string;
  props?: Record<string, any>;
  hasConfirmCallback?: boolean;
}

interface TableActionGroup {
  type: 'group';
  name: string;
  label: string;
  styles?: string;
  icon?: string;
  iconPosition?: string;
  props?: Record<string, any>;
  actions: TableAction[];
}

interface DatatableActionsProps {
  actions: (TableAction | TableActionGroup)[];
  selectedRows: (number | string)[];
  onActionClick: (actionName: string) => void;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const DatatableActions: React.FC<DatatableActionsProps> = ({
  actions,
  selectedRows,
  onActionClick,
  icons = {}
}) => {
  if (!actions || actions.length === 0) {
    return null;
  }

  return (
    <div className="flex items-center space-x-2">
      {actions.map((action, index) => {
        if (action.type === 'group') {
          // Render action group as dropdown
          return (
            <DropdownMenu key={index}>
              <DropdownMenuTrigger asChild>
                <Button
                  variant="outline"
                  size="sm"
                  className={`h-8 px-2 lg:px-3 ${action.styles || ''}`}
                  disabled={selectedRows.length === 0}
                  {...action.props}
                >
                  {action.icon && action.iconPosition !== 'right' && (
                    <IconRenderer iconName={action.icon} className="h-4 w-4 mr-2" icons={icons} />
                  )}
                  <span className={action.icon ? 'lg:block hidden' : ''}>{action.label}</span>
                  {action.icon && action.iconPosition === 'right' && (
                    <IconRenderer iconName={action.icon} className="h-4 w-4 ml-2" icons={icons} />
                  )}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                {action.actions.map((subAction, subIndex) => (
                  <DropdownMenuItem
                    key={subIndex}
                    onClick={() => onActionClick(subAction.name)}
                    className={subAction.styles || ''}
                    {...subAction.props}
                  >
                    {subAction.icon && subAction.iconPosition !== 'right' && (
                      <IconRenderer iconName={subAction.icon} className="h-4 w-4 mr-2" icons={icons} />
                    )}
                    <span>{subAction.label}</span>
                    {subAction.icon && subAction.iconPosition === 'right' && (
                      <IconRenderer iconName={subAction.icon} className="h-4 w-4 ml-2" icons={icons} />
                    )}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
          );
        } else {
          // Render single action as button
          return (
            <Button
              key={index}
              variant="outline"
              size="sm"
              className={`h-8 px-2 lg:px-3 ${action.styles || ''}`}
              onClick={() => onActionClick(action.name)}
              disabled={selectedRows.length === 0}
              {...action.props}
            >
              {action.icon && action.iconPosition !== 'right' && (
                <IconRenderer iconName={action.icon} className="h-4 w-4 mr-2" icons={icons} />
              )}
              <span className={action.icon ? 'lg:block hidden' : ''}>{action.label}</span>
              {action.icon && action.iconPosition === 'right' && (
                <IconRenderer iconName={action.icon} className="h-4 w-4 ml-2" icons={icons} />
              )}
            </Button>
          );
        }
      })}
    </div>
  );
};