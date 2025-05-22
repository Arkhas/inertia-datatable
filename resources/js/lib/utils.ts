import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"
import * as LucideIcons from "lucide-react"
import { type LucideProps } from "lucide-react"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function getIconByName(iconName: string): any | null {
  if (!iconName) return null;

  // Try to get the icon from Lucide icons
  const icon = LucideIcons[iconName as keyof typeof LucideIcons];

  if (!icon) {
    console.warn(`Icon "${iconName}" not found in Lucide icons`);
    return null;
  }

  return icon;
}
