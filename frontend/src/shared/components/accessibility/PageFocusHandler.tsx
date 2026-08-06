import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router';

const focusPageTarget = () => {
  const target = document.querySelector<HTMLElement>(
    '[data-page-focus-target], main h1, [role="main"] h1, main, [role="main"]',
  );

  target?.focus({ preventScroll: true });
};

export const PageFocusHandler = () => {
  const location = useLocation();
  const previousPathRef = useRef<string | null>(null);

  useEffect(() => {
    const path = `${location.pathname}${location.search}${location.hash}`;
    if (previousPathRef.current === path) return;

    const isInitialRoute = previousPathRef.current === null;
    previousPathRef.current = path;
    if (isInitialRoute) return;

    const timeoutId = window.setTimeout(focusPageTarget, 120);

    return () => window.clearTimeout(timeoutId);
  }, [location.hash, location.pathname, location.search]);

  return null;
};
