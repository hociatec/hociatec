import { useEffect } from 'react';

const PRIVATE_ROBOTS_CONTENT = 'noindex,nofollow,noarchive';

export const PrivateRouteMeta = () => {
  useEffect(() => {
    if (typeof document === 'undefined') return;

    let meta = document.head.querySelector('meta[name="robots"]') as HTMLMetaElement | null;
    if (!meta) {
      meta = document.createElement('meta');
      meta.setAttribute('name', 'robots');
      document.head.appendChild(meta);
    }

    meta.setAttribute('content', PRIVATE_ROBOTS_CONTENT);
  }, []);

  return null;
};
