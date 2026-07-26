import { useMemo } from 'react';
import { Popover } from '@headlessui/react';

import { AccountNotificationsPopover } from '@/shared/components/AccountNotificationsPopover';
import { useAccountNotifications } from '@/shared/hooks/useAccountNotifications';

export const AccountNotifications = () => {
  const state = useAccountNotifications();
  const buttonLabel = useMemo(() => {
    if (state.loading || state.readStateLoading) return 'Notifications en cours de chargement';
    if (state.unreadCount === 0 && state.notifications.length === 0) return 'Aucune notification';
    if (state.unreadCount === 0) return 'Notifications, aucune nouvelle';
    return `${state.unreadCount} notification${state.unreadCount > 1 ? 's' : ''} non lue${state.unreadCount > 1 ? 's' : ''}`;
  }, [state.loading, state.notifications.length, state.readStateLoading, state.unreadCount]);

  return (
    <Popover className="site-header__notifications">
      {({ open }) => (
        <AccountNotificationsPopover {...state} buttonLabel={buttonLabel} open={open} />
      )}
    </Popover>
  );
};
