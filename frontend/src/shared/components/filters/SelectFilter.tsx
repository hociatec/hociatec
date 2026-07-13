import type { ChangeEvent } from 'react';

export interface SelectOption {
  value: string;
  label: string;
}

interface SelectFilterProps {
  value: string;
  onChange: (next: string) => void;
  options: SelectOption[];
  ariaLabel?: string;
}

export const SelectFilter = ({ value, onChange, options, ariaLabel }: SelectFilterProps) => (
  <select
    aria-label={ariaLabel}
    value={value}
    onChange={(e: ChangeEvent<HTMLSelectElement>) => onChange(e.target.value)}
    style={{ borderRadius: 999, padding: '10px 18px' }}
  >
    {options.map((opt) => (
      <option key={opt.value} value={opt.value}>{opt.label}</option>
    ))}
  </select>
);

