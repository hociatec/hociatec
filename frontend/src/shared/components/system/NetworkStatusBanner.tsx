import { useEffect, useState } from 'react';

const getOnlineStatus = () =>
  typeof navigator === 'undefined' || typeof navigator.onLine !== 'boolean'
    ? true
    : navigator.onLine;

export const NetworkStatusBanner = () => {
  const [isOnline, setIsOnline] = useState(getOnlineStatus);
  const [hasBeenOffline, setHasBeenOffline] = useState(false);

  useEffect(() => {
    const updateOnline = () => {
      setIsOnline(true);
      setHasBeenOffline(true);
    };
    const updateOffline = () => {
      setIsOnline(false);
      setHasBeenOffline(true);
    };

    window.addEventListener('online', updateOnline);
    window.addEventListener('offline', updateOffline);

    return () => {
      window.removeEventListener('online', updateOnline);
      window.removeEventListener('offline', updateOffline);
    };
  }, []);

  if (isOnline && !hasBeenOffline) {
    return null;
  }

  return (
    <div
      className={isOnline ? 'network-status network-status--online' : 'network-status'}
      role="status"
      aria-live="polite"
      aria-atomic="true"
    >
      {isOnline
        ? 'Connexion rétablie. Les données vont se synchroniser.'
        : 'Vous êtes hors connexion. Les données déjà chargées restent consultables.'}
    </div>
  );
};
