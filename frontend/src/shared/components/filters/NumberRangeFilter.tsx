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

  const parse = (v: string) => {
    if (v === '') return null;
    const parsed = Number(v);
    if (Number.isNaN(parsed)) return null;
    return Math.max(0, parsed);
  };

  return (
    <div className="number-range-filter">
      <label htmlFor={idMin} className="sr-only">Prix min</label>
      <input
        id={idMin}
        type="number"
        min={0}
        step={step}
        value={min ?? ''}
        onChange={(e) => onChange({ min: parse(e.target.value), max })}
        placeholder="Prix min"
      />
      <span className="muted">à</span>
      <label htmlFor={idMax} className="sr-only">Prix max</label>
      <input
        id={idMax}
        type="number"
        min={0}
        step={step}
        value={max ?? ''}
        onChange={(e) => onChange({ min, max: parse(e.target.value) })}
        placeholder="Prix max"
      />
    </div>
  );
};
