import { useEffect, useState } from 'react';

import { useTimeout } from './useTimeout';

export const useDebounce = <T>(value: T, delayMs = 300) => {
  const [debounced, setDebounced] = useState<T>(value);
  const { schedule, clear } = useTimeout();

  useEffect(() => {
    schedule(() => setDebounced(value), delayMs);
    return () => clear();
  }, [clear, delayMs, schedule, value]);

  return debounced;
};
