import { useId } from 'react';

interface NumberRangeFilterProps {
  min?: number | null;
  max?: number | null;
  onChange: (next: { min: number | null; max: number | null }) => void;
  step?: number;
}

export const NumberRangeFilter = ({ min = null, max = null, onChange, step = 1 }: NumberRangeFilterProps) => {
  const idMin = useId();
  const idMax = useId();

  const parse = (v: string) => (v === '' ? null : Number(v));

  return (
    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
      <label htmlFor={idMin} className="sr-only">Min</label>
      <input
        id={idMin}
        type="number"
        step={step}
        value={min ?? ''}
        onChange={(e) => onChange({ min: parse(e.target.value), max })}
        placeholder="Min"
        style={{ width: 100, border: '1px solid rgba(148, 163, 184, 0.6)', borderRadius: 10, padding: '10px 12px' }}
      />
      <span className="muted">à</span>
      <label htmlFor={idMax} className="sr-only">Max</label>
      <input
        id={idMax}
        type="number"
        step={step}
        value={max ?? ''}
        onChange={(e) => onChange({ min, max: parse(e.target.value) })}
        placeholder="Max"
        style={{ width: 100, border: '1px solid rgba(148, 163, 184, 0.6)', borderRadius: 10, padding: '10px 12px' }}
      />
    </div>
  );
};

