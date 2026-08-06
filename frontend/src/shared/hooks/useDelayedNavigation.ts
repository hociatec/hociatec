import { useCallback } from 'react';
import { useNavigate, type NavigateFunction } from 'react-router';
import { useTimeout } from '@/shared/hooks/useTimeout';

type NavigateTarget = Parameters<NavigateFunction>[0];
type NavigateOptions = Parameters<NavigateFunction>[1];

export const useDelayedNavigation = (defaultDelayMs = 500) => {
  const navigate = useNavigate();
  const { schedule } = useTimeout();

  const navigateWithDelay = useCallback(
    (to: NavigateTarget, options?: NavigateOptions, delayMs = defaultDelayMs) => {
      schedule(() => {
        navigate(to, options);
      }, delayMs);
    },
    [defaultDelayMs, navigate, schedule],
  );

  return navigateWithDelay;
};
