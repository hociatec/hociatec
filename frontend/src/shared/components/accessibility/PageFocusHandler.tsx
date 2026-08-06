import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router';

import { useTimeout } from '@/shared/hooks/useTimeout';

const focusPageTarget = () => {
  const target = document.querySelector<HTMLElement>(
    '[data-page-focus-target], main h1, [role="main"] h1, main, [role="main"]',
  );

  target?.focus({ preventScroll: true });
};

export const PageFocusHandler = () => {
  const location = useLocation();
  const previousPathRef = useRef<string | null>(null);
  const { schedule } = useTimeout();

  useEffect(() => {
    const path = `${location.pathname}${location.search}${location.hash}`;
    if (previousPathRef.current === path) return;

    const isInitialRoute = previousPathRef.current === null;
    previousPathRef.current = path;
    if (isInitialRoute) return;

    schedule(focusPageTarget, 120);
  }, [location.hash, location.pathname, location.search]);

  return null;
};
