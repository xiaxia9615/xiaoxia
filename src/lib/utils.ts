import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/** 毫转元显示（1 元 = 1000 毫） */
export function formatMilliAmount(value: number): string {
  return `¥${(value / 1000).toFixed(2)}`;
}
