import { useCallback, useEffect, useMemo, useState } from 'react';

import {
  dismissAccountNotification,
  dismissAccountNotifications,
  fetchAccountNotifications,
  fetchAccountNotificationsReadState,
  markAccountNotificationsSeen,
  type AccountNotificationsReadStateDto,
} from '@/shared/api/accountNotifications';
import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

const MAX_VISIBLE_UNREAD_NOTIFICATIONS = 5;
const emptyReadState: AccountNotificationsReadStateDto = {
  seenSignature: '',
  seenKeys: [],
  dismissedKeys: [],
};

interface UseAccountNotificationsResult {
  hasPartialError: boolean;
  loading: boolean;
  markCurrentNotificationsAsSeen: () => boolean;
  notifications: AccountNotificationItem[];
  onDismissAllNotifications: () => void;
  onDismissNotification: (notificationKey: string) => void;
  onNotificationClick: (notificationKey: string) => void;
  readStateLoading: boolean;
  seenKeys: Set<string>;
  unreadCount: number;
}

export const useAccountNotifications = (): UseAccountNotificationsResult => {
  const [notifications, setNotifications] = useState<AccountNotificationItem[]>([]);
  const [readState, setReadState] = useState(emptyReadState);
  const [loading, setLoading] = useState(true);
  const [readStateLoading, setReadStateLoading] = useState(true);
  const [hasPartialError, setHasPartialError] = useState(false);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setHasPartialError(false);

    void Promise.allSettled([fetchAccountNotifications()]).then(([notificationsResult]) => {
        if (cancelled) return;

        setNotifications(notificationsResult.status === 'fulfilled' ? notificationsResult.value : []);
        setHasPartialError(notificationsResult.status === 'rejected');
        setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    let cancelled = false;
    setReadStateLoading(true);

    void fetchAccountNotificationsReadState()
      .then((nextReadState) => {
        if (!cancelled) {
          setReadState(nextReadState);
          setReadStateLoading(false);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setReadState(emptyReadState);
          setReadStateLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const seenKeys = useMemo(() => new Set(readState.seenKeys), [readState.seenKeys]);
  const dismissedKeys = useMemo(() => new Set(readState.dismissedKeys), [readState.dismissedKeys]);
  const availableNotifications = useMemo(
    () => notifications.filter((notification) => !dismissedKeys.has(notification.key)),
    [dismissedKeys, notifications],
  );
  const visibleNotifications = useMemo(() => {
    if (readStateLoading) return availableNotifications;

    let visibleUnreadCount = 0;
    return availableNotifications.filter((notification) => {
      if (seenKeys.has(notification.key)) return true;
      if (visibleUnreadCount >= MAX_VISIBLE_UNREAD_NOTIFICATIONS) return false;
      visibleUnreadCount += 1;
      return true;
    });
  }, [availableNotifications, readStateLoading, seenKeys]);
  const unreadCount = readStateLoading
    ? 0
    : availableNotifications.filter((notification) => !seenKeys.has(notification.key)).length;

  const markNotificationsAsSeen = useCallback(
    async (keys: string[]) => {
      const nextKeys = keys.filter((key) => !seenKeys.has(key));
      if (readStateLoading || nextKeys.length === 0) return;

      setReadState((current) => ({
        ...current,
        seenKeys: Array.from(new Set([...current.seenKeys, ...nextKeys])),
      }));
      try {
        setReadState(await markAccountNotificationsSeen(nextKeys));
      } catch {
        // L'état optimiste reste affiché; le serveur sera resynchronisé à la prochaine ouverture.
      }
    },
    [readStateLoading, seenKeys],
  );

  const markCurrentNotificationsAsSeen = useCallback(() => {
    if (loading || readStateLoading) return false;
    const keys = availableNotifications
      .filter((notification) => !seenKeys.has(notification.key))
      .map((notification) => notification.key);
    if (keys.length > 0) void markNotificationsAsSeen(keys);
    return true;
  }, [availableNotifications, loading, markNotificationsAsSeen, readStateLoading, seenKeys]);

  const onNotificationClick = useCallback(
    (notificationKey: string) => void markNotificationsAsSeen([notificationKey]),
    [markNotificationsAsSeen],
  );

  const onDismissNotification = useCallback(async (notificationKey: string) => {
    setReadState((current) => ({
      ...current,
      dismissedKeys: Array.from(new Set([...current.dismissedKeys, notificationKey])),
      seenKeys: Array.from(new Set([...current.seenKeys, notificationKey])),
    }));
    try {
      setReadState(await dismissAccountNotification(notificationKey));
    } catch {
      setReadState((current) => ({
        ...current,
        dismissedKeys: current.dismissedKeys.filter((key) => key !== notificationKey),
      }));
    }
  }, []);

  const onDismissAllNotifications = useCallback(async () => {
    const keys = availableNotifications.map((notification) => notification.key);
    if (keys.length === 0) return;

    setReadState((current) => ({
      ...current,
      dismissedKeys: Array.from(new Set([...current.dismissedKeys, ...keys])),
      seenKeys: Array.from(new Set([...current.seenKeys, ...keys])),
    }));

    try {
      setReadState(await dismissAccountNotifications(keys));
    } catch {
      setReadState((current) => ({
        ...current,
        dismissedKeys: current.dismissedKeys.filter((key) => !keys.includes(key)),
      }));
    }
  }, [availableNotifications]);

  return {
    hasPartialError,
    loading,
    markCurrentNotificationsAsSeen,
    notifications: visibleNotifications,
    onDismissAllNotifications,
    onDismissNotification,
    onNotificationClick,
    readStateLoading,
    seenKeys,
    unreadCount,
  };
};
