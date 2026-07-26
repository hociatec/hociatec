import type { PropsWithChildren, ReactNode } from 'react';

import { EmptyState, LoadingState, StableContent } from '@/shared/components/ui/page-state';
import { cn } from '@/lib/utils';

interface AdminTableShellProps extends PropsWithChildren {
  className?: string;
}

export const AdminTableShell = ({ children, className }: AdminTableShellProps) => (
  <div
    className={cn(
      'overflow-x-auto rounded-xl border border-brand-100 bg-white shadow-sm',
      className,
    )}
  >
    {children}
  </div>
);

interface AdminListStateProps {
  loading: boolean;
  isEmpty: boolean;
  loadingLabel: string;
  emptyLabel: ReactNode;
  children: ReactNode;
}

export const AdminListState = ({
  loading,
  isEmpty,
  loadingLabel,
  emptyLabel,
  children,
}: AdminListStateProps) => {
  if (loading && isEmpty) {
    return <LoadingState>{loadingLabel}</LoadingState>;
  }

  if (!loading && isEmpty) {
    return <EmptyState>{emptyLabel}</EmptyState>;
  }

  return (
    <StableContent loading={loading} hasContent={!isEmpty}>
      {children}
    </StableContent>
  );
};

interface AdminMetricGridProps extends PropsWithChildren {
  columns?: 3 | 4;
}

export const AdminMetricGrid = ({ children, columns = 3 }: AdminMetricGridProps) => (
  <div className={cn('mb-6 grid gap-4', columns === 4 ? 'md:grid-cols-4' : 'md:grid-cols-3')}>
    {children}
  </div>
);

export const AdminMetricCard = ({ label, value }: { label: string; value: ReactNode }) => (
  <div className="rounded-xl border border-brand-100 bg-white p-4">
    <p className="text-sm text-stone-500">{label}</p>
    <strong className="mt-1 block text-2xl text-brand-900">{value}</strong>
  </div>
);
