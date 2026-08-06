import { useCallback, useEffect, useRef } from 'react';

type TimeoutHandle = ReturnType<typeof setTimeout>;

export const useTimeout = () => {
  const timeoutRef = useRef<TimeoutHandle | null>(null);

  const clearTimeoutRef = useCallback(() => {
    if (timeoutRef.current === null || typeof window === 'undefined') {
      return;
    }

    clearTimeout(timeoutRef.current);
    timeoutRef.current = null;
  }, []);

  const schedule = useCallback(
    (callback: () => void, delayMs: number) => {
      clearTimeoutRef();

      if (typeof window === 'undefined') {
        return;
      }

      timeoutRef.current = setTimeout(() => {
        timeoutRef.current = null;
        callback();
      }, delayMs);
    },
    [clearTimeoutRef],
  );

  useEffect(() => clearTimeoutRef, [clearTimeoutRef]);

  return { schedule, clear: clearTimeoutRef };
};
