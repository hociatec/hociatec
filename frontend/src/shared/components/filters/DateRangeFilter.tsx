import { useId } from 'react';

import { cn } from '@/shared/lib/cn';

interface DateRangeFilterProps {
  from?: string | null;
  to?: string | null;
  onChange: (next: { from: string | null; to: string | null }) => void;
  className?: string;
}

export const DateRangeFilter = ({ from = null, to = null, onChange, className }: DateRangeFilterProps) => {
  const idFrom = useId();
  const idTo = useId();

  return (
    <div className={cn('date-range-filter', className)}>
      <label htmlFor={idFrom} className="sr-only">Du</label>
      <input
        id={idFrom}
        type="date"
        value={from ?? ''}
        onChange={(e) => onChange({ from: e.target.value || null, to })}
      />
      <span className="muted">à</span>
      <label htmlFor={idTo} className="sr-only">Au</label>
      <input
        id={idTo}
        type="date"
        value={to ?? ''}
        onChange={(e) => onChange({ from, to: e.target.value || null })}
      />
    </div>
  );
};
