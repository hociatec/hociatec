export const accountNotificationsQueryKeys = {
  all: () => ['account'] as const,
  notifications: () => [...accountNotificationsQueryKeys.all(), 'notifications'] as const,
  notificationsReadState: () => [...accountNotificationsQueryKeys.all(), 'notifications-read-state'] as const,
};
