export const accountQueryKeys = {
  dashboard: () => ['account', 'dashboard'] as const,
  notifications: () => ['account', 'notifications'] as const,
  notificationsReadState: () => ['account', 'notifications-read-state'] as const,
};
