import { useQuery } from '@tanstack/react-query';

import { fetchAccountNotifications } from '@/shared/api/accountNotifications';
import type { AccountNotificationItem } from '@/shared/types/accountNotifications';
import { accountQueryKeys } from '@/features/account/queryKeys';

export const useNotificationsLoader = () => {
  const notificationsQuery = useQuery<AccountNotificationItem[], Error>({
    queryKey: accountQueryKeys.notifications(),
    queryFn: fetchAccountNotifications,
  });

  return {
    hasPartialError: notificationsQuery.isError,
    loading: notificationsQuery.isLoading,
    notifications: notificationsQuery.data ?? [],
  };
};
