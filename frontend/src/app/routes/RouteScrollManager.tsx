import { useLayoutEffect } from 'react';
import { useLocation } from 'react-router-dom';

export const RouteScrollManager = () => {
  const location = useLocation();

  useLayoutEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const preserveScroll =
      Boolean((location.state as { preserveScroll?: boolean } | null)?.preserveScroll);

    if (preserveScroll) {
      return;
    }

    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
  }, [location.pathname, location.search, location.hash, location.state]);

  return null;
};
