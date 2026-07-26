import { useEffect, useId, useState } from 'react';
import { useDebounce } from '@/shared/hooks/useDebounce';

interface SearchFilterProps {
  value: string;
  onChange: (next: string) => void;
  placeholder?: string;
  debounceMs?: number;
  className?: string;
}

export const SearchFilter = ({
  value,
  onChange,
  placeholder = 'Rechercher…',
  debounceMs = 300,
  className,
}: SearchFilterProps) => {
  const inputId = useId();
  const [raw, setRaw] = useState(value);
  const debounced = useDebounce(raw, debounceMs);

  useEffect(() => {
    setRaw(value);
  }, [value]);
  useEffect(() => {
    if (debounced !== value) onChange(debounced);
  }, [debounced]);

  return (
    <div className={className ?? 'catalog-filter-bar__search'}>
      <label htmlFor={inputId} className="sr-only">
        Recherche
      </label>
      <input
        id={inputId}
        type="search"
        placeholder={placeholder}
        value={raw}
        onChange={(e) => setRaw(e.target.value)}
      />
    </div>
  );
};
