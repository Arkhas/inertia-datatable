import React from 'react';
import { getIconByName } from "../../lib/utils";

interface IconRendererProps {
  iconName: string;
  className?: string;
  icons?: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>>;
}

export const IconRenderer: React.FC<IconRendererProps> = ({ 
  iconName, 
  className = "h-4 w-4", 
  icons = {} 
}) => {
  // First check if the icon is provided in the icons prop
  if (icons && icons[iconName]) {
    const Icon = icons[iconName];
    return <Icon className={className} />;
  }

  // If not found in props, try to get it from Lucide icons
  const Icon = getIconByName(iconName);
  if (Icon) {
    return <Icon className={className} />;
  }

  return null;
};

export const getIconComponent = (
  iconName: string, 
  icons: Record<string, React.ComponentType<React.SVGProps<SVGSVGElement>>> = {}
): React.ComponentType<React.SVGProps<SVGSVGElement>> | undefined => {
  // First check if the icon is provided in the icons prop
  if (icons && icons[iconName]) {
    return icons[iconName];
  }

  // If not found in props, try to get it from Lucide icons
  const icon = getIconByName(iconName);
  return icon || undefined;
};