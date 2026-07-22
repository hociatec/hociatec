import type { PropsWithChildren, ReactNode } from 'react';

import { cn } from '@/shared/lib/cn';

interface FilterBarProps {
  className?: string;
  rightActions?: ReactNode;
}

export const FilterBar = ({ className, rightActions, children }: PropsWithChildren<FilterBarProps>) => (
  <div className={cn(className ?? 'catalog-filter-bar', rightActions ? 'justify-between' : undefined)}>
    <div className="filter-bar__controls">
      {children}
    </div>
    {rightActions && (
      <div className="filter-bar__actions">
        {rightActions}
      </div>
    )}
  </div>
);
