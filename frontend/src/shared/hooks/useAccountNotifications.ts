import { useCallback, useEffect, useMemo, useState } from 'react';

import { fetchMyAppointments } from '@/features/appointments/api/appointmentsApi';
import { fetchMyAudits } from '@/features/audits/api/auditsApi';
import { fetchPendingReviews } from '@/features/orders/api';
import { fetchMyTrainingEnrollments } from '@/features/trainings/api/trainingsApi';
import { fetchMyVouchers } from '@/features/vouchers/api/vouchersApi';
import {
  dismissAccountNotification,
  fetchAccountNotificationsReadState,
  markAccountNotificationsSeen,
  type AccountNotificationsReadStateDto,
} from '@/shared/api/accountNotifications';
import { buildAccountNotifications } from '@/shared/lib/accountNotifications';
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

    void Promise.allSettled([
      fetchPendingReviews(),
      fetchMyAppointments(),
      fetchMyTrainingEnrollments(),
      fetchMyAudits(),
      fetchMyVouchers(),
    ]).then(([reviewsResult, appointmentsResult, trainingsResult, auditsResult, vouchersResult]) => {
      if (cancelled) return;

      setNotifications(
        buildAccountNotifications({
          pendingReviews: reviewsResult.status === 'fulfilled' ? reviewsResult.value : [],
          appointments:
            appointmentsResult.status === 'fulfilled'
              ? appointmentsResult.value.upcoming ?? []
              : [],
          trainings: trainingsResult.status === 'fulfilled' ? trainingsResult.value : [],
          audits: auditsResult.status === 'fulfilled' ? auditsResult.value : [],
          vouchers: vouchersResult.status === 'fulfilled' ? vouchersResult.value : [],
        }),
      );
      setHasPartialError(
        [reviewsResult, appointmentsResult, trainingsResult, auditsResult, vouchersResult].some(
          (result) => result.status === 'rejected',
        ),
      );
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
    const keys = visibleNotifications
      .filter((notification) => !seenKeys.has(notification.key))
      .map((notification) => notification.key);
    if (keys.length > 0) void markNotificationsAsSeen(keys);
    return true;
  }, [loading, markNotificationsAsSeen, readStateLoading, seenKeys, visibleNotifications]);

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

  return {
    hasPartialError,
    loading,
    markCurrentNotificationsAsSeen,
    notifications: visibleNotifications,
    onDismissNotification,
    onNotificationClick,
    readStateLoading,
    seenKeys,
    unreadCount,
  };
};
