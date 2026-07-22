import type { ChangeEvent } from 'react';

import { cn } from '@/shared/lib/cn';

export interface SelectOption {
  value: string;
  label: string;
}

interface SelectFilterProps {
  value: string;
  onChange: (next: string) => void;
  options: SelectOption[];
  ariaLabel?: string;
  className?: string;
}

export const SelectFilter = ({ value, onChange, options, ariaLabel, className }: SelectFilterProps) => (
  <select
    aria-label={ariaLabel}
    value={value}
    onChange={(e: ChangeEvent<HTMLSelectElement>) => onChange(e.target.value)}
    className={cn('select-filter', className)}
  >
    {options.map((opt) => (
      <option key={opt.value} value={opt.value}>{opt.label}</option>
    ))}
  </select>
);
