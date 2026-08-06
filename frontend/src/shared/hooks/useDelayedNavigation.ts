import { useCallback } from 'react';
import { useNavigate, type NavigateOptions, type To } from 'react-router';
import { useTimeout } from '@/shared/hooks/useTimeout';

type NavigateWithDelay = {
  (to: number, delayMs?: number): void;
  (to: To, options?: NavigateOptions, delayMs?: number): void;
};

export const useDelayedNavigation = (defaultDelayMs = 500) => {
  const navigate = useNavigate();
  const { schedule } = useTimeout();

  const navigateWithDelay = useCallback(
    (to: To | number, optionsOrDelayMs?: NavigateOptions | number, delayMs?: number) => {
      const finalDelayMs =
        typeof optionsOrDelayMs === 'number'
          ? optionsOrDelayMs
          : delayMs === undefined
            ? defaultDelayMs
            : delayMs;
      const options = typeof optionsOrDelayMs === 'number' ? undefined : optionsOrDelayMs;

      schedule(() => {
        if (typeof to === 'number') {
          navigate(to);
          return;
        }

        if (options === undefined) {
          navigate(to);
        } else {
          navigate(to, options);
        }
      }, finalDelayMs);
    },
    [defaultDelayMs, navigate, schedule],
  );

  return navigateWithDelay as NavigateWithDelay;
};
