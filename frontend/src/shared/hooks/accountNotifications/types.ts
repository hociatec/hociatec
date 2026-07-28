import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

export interface UseAccountNotificationsResult {
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
