import { useEffect, useRef } from 'react';
import { Bell, Trash2 } from 'lucide-react';
import { Link } from 'react-router';
import { PopoverButton, PopoverPanel } from '@headlessui/react';

import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

const notificationLinkLabel = (notification: AccountNotificationItem): string => {
  if (notification.type.startsWith('beta_') || notification.to.startsWith('/beta')) {
    return 'Accéder à l’espace bêta';
  }

  return 'Consulter';
};

interface AccountNotificationsPopoverProps {
  buttonLabel: string;
  hasPartialError: boolean;
  loading: boolean;
  markCurrentNotificationsAsSeen: () => boolean;
  notifications: AccountNotificationItem[];
  onDismissAllNotifications: () => void;
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
  onDismissAllNotifications,
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
          <span className="site-header__badge" aria-hidden="true">
            {unreadCount}
          </span>
        ) : null}
      </PopoverButton>
      <PopoverPanel
        className="site-header__notifications-panel"
        aria-label="Notifications du compte"
      >
        {loading ? (
          <p aria-hidden="true">Chargement des notifications...</p>
        ) : notifications.length === 0 ? (
          <p>Aucune notification prioritaire.</p>
        ) : (
          <>
            <div className="site-header__notifications-actions">
              <button type="button" onClick={onDismissAllNotifications}>
                Tout supprimer
              </button>
            </div>
            {notifications.map((notification) => (
              <div
                key={notification.key}
                className={`site-header__notifications-item${
                  seenKeys.has(notification.key) ? '' : ' site-header__notifications-item--unread'
                }`}
              >
                <div className="site-header__notifications-content">
                  <p className="site-header__notifications-title">{notification.label}</p>
                  <p className="site-header__notifications-message">{notification.message}</p>
                  <Link to={notification.to} onClick={() => onNotificationClick(notification.key)}>
                    {notificationLinkLabel(notification)}
                  </Link>
                </div>
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
            ))}
          </>
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
