import { type ReactNode } from 'react';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginationMeta } from '@/shared/types/api';

export const operationsCardClass = 'rounded-2xl border border-brand-100 bg-white p-5 shadow-sm';

export const operationsSecondaryActionClass =
  'rounded-xl border border-brand-100 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-700';

export const StatCard = ({
  label,
  value,
  helper,
  tone = 'neutral',
}: {
  label: string;
  value: number;
  helper: string;
  tone?: 'neutral' | 'warning' | 'danger';
}) => {
  const toneClass = {
    neutral: 'border-brand-100 bg-white text-brand-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    danger: 'border-red-200 bg-red-50 text-red-950',
  }[tone];

  return (
    <div className={`rounded-2xl border p-5 shadow-sm ${toneClass}`}>
      <div className="text-sm font-medium opacity-75">{label}</div>
      <div className="mt-2 text-3xl font-semibold">{value}</div>
      <p className="mt-2 text-xs leading-5 opacity-75">{helper}</p>
    </div>
  );
};

export const ActionCard = ({
  title,
  description,
  warning,
  children,
}: {
  title?: string;
  description?: string;
  warning?: string;
  children: ReactNode;
}) => (
  <div className={operationsCardClass}>
    <div className="mb-4">
      {title ? <h2 className="text-lg font-semibold text-brand-900">{title}</h2> : null}
      {description ? <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p> : null}
      {warning && (
        <p className="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-900">
          {warning}
        </p>
      )}
    </div>
    <div className="space-y-4">{children}</div>
  </div>
);

export const Field = ({
  label,
  helper,
  className = '',
  children,
}: {
  label: string;
  helper?: string;
  className?: string;
  children: ReactNode;
}) => (
  <label className={`block ${className}`}>
    <span className="text-sm font-medium text-stone-700">{label}</span>
    <span className="mt-1 block">{children}</span>
    {helper && <span className="mt-1 block text-xs leading-5 text-stone-500">{helper}</span>}
  </label>
);

export const List = ({
  title,
  items,
  meta,
  onPageChange,
}: {
  title?: string;
  items: Array<{ key: string | number; title: ReactNode; meta: ReactNode; action?: ReactNode }>;
  meta: PaginationMeta;
  onPageChange: (updater: (page: number) => number) => void;
}) => (
    <div className={operationsCardClass}>
      {title ? <h2 className="text-lg font-semibold">{title}</h2> : null}
      <div className="mt-4 space-y-3">
        {items.length === 0 ? (
          <p className="text-sm text-stone-500">Aucun élément.</p>
        ) : (
          items.map((item) => (
            <div key={item.key} className="rounded-xl border border-brand-100 bg-brand-50 p-3">
              <div className="font-medium text-brand-900">{item.title}</div>
              <div className="mt-1 text-sm text-stone-500">{item.meta}</div>
              {item.action && <div className="mt-3">{item.action}</div>}
            </div>
          ))
        )}
      </div>
      <PaginationControls
        page={meta.page}
        total={meta.total}
        totalLabel="élément"
        totalPages={meta.totalPages}
        onPageChange={onPageChange}
      />
    </div>
);
