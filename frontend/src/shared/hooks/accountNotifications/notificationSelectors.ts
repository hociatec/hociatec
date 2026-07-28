import type { AccountNotificationItem } from '@/shared/types/accountNotifications';
import { MAX_VISIBLE_UNREAD_NOTIFICATIONS } from './constants';

export const filterAvailableNotifications = (
  notifications: AccountNotificationItem[],
  dismissedKeys: Set<string>,
) => notifications.filter((notification) => !dismissedKeys.has(notification.key));

export const filterVisibleNotifications = (
  notifications: AccountNotificationItem[],
  seenKeys: Set<string>,
  readStateLoading: boolean,
) => {
  if (readStateLoading) return notifications;

  let visibleUnreadCount = 0;
  return notifications.filter((notification) => {
    if (seenKeys.has(notification.key)) return true;
    if (visibleUnreadCount >= MAX_VISIBLE_UNREAD_NOTIFICATIONS) return false;
    visibleUnreadCount += 1;
    return true;
  });
};

export const countUnreadNotifications = (
  notifications: AccountNotificationItem[],
  seenKeys: Set<string>,
  readStateLoading: boolean,
) => {
  if (readStateLoading) return 0;

  return notifications.filter((notification) => !seenKeys.has(notification.key)).length;
};
