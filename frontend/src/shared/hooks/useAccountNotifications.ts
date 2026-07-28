import { useMemo } from 'react';

import {
  countUnreadNotifications,
  filterAvailableNotifications,
  filterVisibleNotifications,
} from './accountNotifications/notificationSelectors';
import type { UseAccountNotificationsResult } from './accountNotifications/types';
import { useNotificationActions } from './accountNotifications/useNotificationActions';
import { useNotificationReadState } from './accountNotifications/useNotificationReadState';
import { useNotificationsLoader } from './accountNotifications/useNotificationsLoader';

export const useAccountNotifications = (): UseAccountNotificationsResult => {
  const { hasPartialError, loading, notifications } = useNotificationsLoader();
  const { dismissedKeys, readStateLoading, seenKeys, setReadState } = useNotificationReadState();

  const availableNotifications = useMemo(
    () => filterAvailableNotifications(notifications, dismissedKeys),
    [dismissedKeys, notifications],
  );
  const visibleNotifications = useMemo(
    () => filterVisibleNotifications(availableNotifications, seenKeys, readStateLoading),
    [availableNotifications, readStateLoading, seenKeys],
  );
  const unreadCount = useMemo(
    () => countUnreadNotifications(availableNotifications, seenKeys, readStateLoading),
    [availableNotifications, readStateLoading, seenKeys],
  );

  const actions = useNotificationActions({
    availableNotifications,
    loading,
    readStateLoading,
    seenKeys,
    setReadState,
  });

  return {
    hasPartialError,
    loading,
    notifications: visibleNotifications,
    readStateLoading,
    seenKeys,
    unreadCount,
    ...actions,
  };
};
