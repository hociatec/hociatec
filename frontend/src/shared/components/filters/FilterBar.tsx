import type { PropsWithChildren, ReactNode } from 'react';

interface FilterBarProps {
  className?: string;
  rightActions?: ReactNode;
}

export const FilterBar = ({ className, rightActions, children }: PropsWithChildren<FilterBarProps>) => (
  <div className={(className ?? 'catalog-filter-bar') + (rightActions ? ' justify-between' : '')}>
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center', flex: 1 }}>
      {children}
    </div>
    {rightActions && (
      <div style={{ marginLeft: 'auto' }}>
        {rightActions}
      </div>
    )}
  </div>
);

