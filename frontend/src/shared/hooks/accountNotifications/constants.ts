import type { AccountNotificationsReadStateDto } from '@/shared/api/accountNotifications';

export const MAX_VISIBLE_UNREAD_NOTIFICATIONS = 5;

export const emptyReadState: AccountNotificationsReadStateDto = {
  seenSignature: '',
  seenKeys: [],
  dismissedKeys: [],
};
