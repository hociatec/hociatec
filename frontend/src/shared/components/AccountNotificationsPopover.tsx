import { useEffect, useRef } from 'react';
import { Bell, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import { PopoverButton, PopoverPanel } from '@headlessui/react';

import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

interface AccountNotificationsPopoverProps {
  buttonLabel: string;
  hasPartialError: boolean;
  loading: boolean;
  markCurrentNotificationsAsSeen: () => boolean;
  notifications: AccountNotificationItem[];
  onDismissNotification: (notificationKey: string) => void;
  onNotificationClick: (notificationKey: string) => void;
  open: boolean;
  seenKeys: Set<string>;
  unreadCount: number;
}

export const AccountNotificationsPopover = ({
  buttonLabel,
  hasPartialError,
  loading,
  markCurrentNotificationsAsSeen,
  notifications,
  onDismissNotification,
  onNotificationClick,
  open,
  seenKeys,
  unreadCount,
}: AccountNotificationsPopoverProps) => {
  const hasMarkedCurrentOpeningRef = useRef(false);

  useEffect(() => {
    if (!open) {
      hasMarkedCurrentOpeningRef.current = false;
      return;
    }
    if (!hasMarkedCurrentOpeningRef.current && markCurrentNotificationsAsSeen()) {
      hasMarkedCurrentOpeningRef.current = true;
    }
  }, [markCurrentNotificationsAsSeen, open]);

  return (
    <>
      <PopoverButton className="site-header__notifications-button" aria-label={buttonLabel}>
        <Bell aria-hidden="true" />
        <span className="site-header__notifications-label">Notifications</span>
        {!open && unreadCount > 0 ? (
          <span className="site-header__badge" aria-hidden="true">{unreadCount}</span>
        ) : null}
      </PopoverButton>
      <PopoverPanel className="site-header__notifications-panel" aria-label="Notifications du compte">
        {loading ? (
          <p aria-hidden="true">Chargement des notifications...</p>
        ) : notifications.length === 0 ? (
          <p>Aucune notification prioritaire.</p>
        ) : (
          notifications.map((notification) => (
            <div
              key={notification.key}
              className={`site-header__notifications-item${
                seenKeys.has(notification.key) ? '' : ' site-header__notifications-item--unread'
              }`}
            >
              <Link to={notification.to} onClick={() => onNotificationClick(notification.key)}>
                {notification.label}
              </Link>
              <button
                type="button"
                className="site-header__notifications-dismiss"
                onClick={(event) => {
                  event.preventDefault();
                  event.stopPropagation();
                  onDismissNotification(notification.key);
                }}
              >
                <Trash2 aria-hidden="true" />
                Supprimer
              </button>
            </div>
          ))
        )}
        {hasPartialError ? (
          <p className="site-header__notifications-warning">
            Certaines notifications n&apos;ont pas pu être chargées.
          </p>
        ) : null}
      </PopoverPanel>
    </>
  );
};
