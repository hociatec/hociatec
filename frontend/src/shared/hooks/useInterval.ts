import { useEffect, useRef } from 'react';

export const useInterval = (callback: () => void, delayMs: number | null) => {
  const callbackRef = useRef(callback);

  useEffect(() => {
    callbackRef.current = callback;
  }, [callback]);

  useEffect(() => {
    if (typeof window === 'undefined' || delayMs === null) {
      return undefined;
    }

    const intervalId = window.setInterval(() => {
      callbackRef.current();
    }, delayMs);

    return () => window.clearInterval(intervalId);
  }, [delayMs]);
};
