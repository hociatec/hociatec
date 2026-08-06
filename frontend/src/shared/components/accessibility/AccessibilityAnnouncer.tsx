import { useEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router';

import { readSessionStorage, removeSessionStorage } from '@/shared/lib/http/storage';

const MAX_ANNOUNCEMENT_LENGTH = 180;

const normalizeAnnouncementText = (value: string | null | undefined) =>
  (value ?? '').replace(/\s+/g, ' ').trim().slice(0, MAX_ANNOUNCEMENT_LENGTH);

const ROUTE_ANNOUNCEMENT_KEY = 'hociatec.a11y.route-announcement';

export const AccessibilityAnnouncer = () => {
  const location = useLocation();
  const previousPathRef = useRef<string | null>(null);
  const previousAnnouncementRef = useRef('');
  const [routeAnnouncement, setRouteAnnouncement] = useState('');

  useEffect(() => {
    const path = `${location.pathname}${location.search}${location.hash}`;
    if (previousPathRef.current === path) {
      return;
    }

    const isInitialRoute = previousPathRef.current === null;
    previousPathRef.current = path;

    if (isInitialRoute) {
      return;
    }

    const timeoutId = window.setTimeout(() => {
      const announcement = readSessionStorage(ROUTE_ANNOUNCEMENT_KEY) ?? '';
      removeSessionStorage(ROUTE_ANNOUNCEMENT_KEY);

      const normalizedAnnouncement = normalizeAnnouncementText(announcement);
      if (normalizedAnnouncement && normalizedAnnouncement !== previousAnnouncementRef.current) {
        previousAnnouncementRef.current = normalizedAnnouncement;
        setRouteAnnouncement(normalizedAnnouncement);
      } else {
        setRouteAnnouncement('');
      }
    }, 120);

    return () => window.clearTimeout(timeoutId);
  }, [location.hash, location.pathname, location.search]);

  return (
    <div className="sr-only" role="status" aria-live="polite" aria-atomic="true">
      {routeAnnouncement}
    </div>
  );
};
