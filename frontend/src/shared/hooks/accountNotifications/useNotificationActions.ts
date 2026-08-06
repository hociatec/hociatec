import { useCallback } from 'react';
import type { Dispatch, SetStateAction } from 'react';

import {
  dismissAccountNotification,
  dismissAccountNotifications,
  markAccountNotificationsSeen,
  type AccountNotificationsReadStateDto,
} from '@/shared/api/accountNotifications';
import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

type SetReadState = Dispatch<SetStateAction<AccountNotificationsReadStateDto>>;

export const useNotificationActions = ({
  availableNotifications,
  loading,
  readStateLoading,
  seenKeys,
  setReadState,
}: {
  availableNotifications: AccountNotificationItem[];
  loading: boolean;
  readStateLoading: boolean;
  seenKeys: Set<string>;
  setReadState: SetReadState;
}) => {
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
      }
    },
    [readStateLoading, seenKeys, setReadState],
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

  const onDismissNotification = useCallback(
    async (notificationKey: string) => {
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
    },
    [setReadState],
  );

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
  }, [availableNotifications, setReadState]);

  return { markCurrentNotificationsAsSeen, onDismissAllNotifications, onDismissNotification, onNotificationClick };
};
