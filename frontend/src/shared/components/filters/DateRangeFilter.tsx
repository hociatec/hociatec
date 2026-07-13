import { useId } from 'react';

interface DateRangeFilterProps {
  from?: string | null;
  to?: string | null;
  onChange: (next: { from: string | null; to: string | null }) => void;
}

export const DateRangeFilter = ({ from = null, to = null, onChange }: DateRangeFilterProps) => {
  const idFrom = useId();
  const idTo = useId();

  return (
    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
      <label htmlFor={idFrom} className="sr-only">Du</label>
      <input
        id={idFrom}
        type="date"
        value={from ?? ''}
        onChange={(e) => onChange({ from: e.target.value || null, to })}
        style={{ border: '1px solid rgba(148, 163, 184, 0.6)', borderRadius: 10, padding: '10px 12px' }}
      />
      <span className="muted">à</span>
      <label htmlFor={idTo} className="sr-only">Au</label>
      <input
        id={idTo}
        type="date"
        value={to ?? ''}
        onChange={(e) => onChange({ from, to: e.target.value || null })}
        style={{ border: '1px solid rgba(148, 163, 184, 0.6)', borderRadius: 10, padding: '10px 12px' }}
      />
    </div>
  );
};

