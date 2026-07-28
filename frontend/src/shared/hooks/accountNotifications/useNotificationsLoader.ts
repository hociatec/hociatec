import { useEffect, useState } from 'react';

import { fetchAccountNotifications } from '@/shared/api/accountNotifications';
import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

export const useNotificationsLoader = () => {
  const [notifications, setNotifications] = useState<AccountNotificationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [hasPartialError, setHasPartialError] = useState(false);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setHasPartialError(false);

    void fetchAccountNotifications()
      .then((items) => {
        if (cancelled) return;
        setNotifications(items);
      })
      .catch(() => {
        if (cancelled) return;
        setNotifications([]);
        setHasPartialError(true);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return { hasPartialError, loading, notifications };
};
