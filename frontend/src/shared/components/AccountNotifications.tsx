import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Bell } from 'lucide-react';
import { Link } from 'react-router-dom';

import { fetchMyAppointments } from '@/features/appointments/api';
import { fetchMyAudits, type AuditListItemDto } from '@/features/audits/api';
import { fetchPendingReviews } from '@/features/orders/api';
import {
  fetchMyTrainingEnrollments,
  type TrainingEnrollmentDto,
} from '@/features/trainings/api';
import { fetchMyVouchers, type MyVoucherDto } from '@/features/vouchers/api';
import {
  dismissAccountNotification,
  fetchAccountNotificationsReadState,
  markAccountNotificationsSeen,
  type AccountNotificationsReadStateDto,
} from '@/shared/api/accountNotifications';
import { Popover, PopoverButton, PopoverPanel } from '@/shared/components/ui/popover';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

const AUDIT_STATUS_LABELS: Record<AuditListItemDto['status'], string> = {
  new: 'Non commencé',
  in_progress: 'En cours',
  review: 'En revue',
  done: 'Finalisé',
};

const MAX_VISIBLE_UNREAD_NOTIFICATIONS = 5;
const emptyReadState: AccountNotificationsReadStateDto = {
  seenSignature: '',
  seenKeys: [],
  dismissedKeys: [],
};

const isVoucherUsable = (voucher: MyVoucherDto, now: number) => {
  if (!voucher.isActive) return false;
  if (voucher.startsAt && new Date(voucher.startsAt).getTime() > now) return false;
  if (voucher.endsAt && new Date(voucher.endsAt).getTime() < now) return false;
  return true;
};

const getNextTraining = (trainings: TrainingEnrollmentDto[], now: number) =>
  trainings
    .filter((training) => training.status !== 'cancelled' && new Date(training.scheduledStartsAt).getTime() >= now)
    .sort((left, right) => new Date(left.scheduledStartsAt).getTime() - new Date(right.scheduledStartsAt).getTime())[0] ?? null;

interface AccountNotificationItem {
  key: string;
  label: string;
  to: string;
}

export const AccountNotifications = () => {
  const [notifications, setNotifications] = useState<AccountNotificationItem[]>([]);
  const [readState, setReadState] = useState<AccountNotificationsReadStateDto>(emptyReadState);
  const [loading, setLoading] = useState(true);
  const [readStateLoading, setReadStateLoading] = useState(true);
  const [hasPartialError, setHasPartialError] = useState(false);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setHasPartialError(false);

    void Promise.allSettled([
      fetchPendingReviews(),
      fetchMyAppointments(),
      fetchMyTrainingEnrollments(),
      fetchMyAudits(),
      fetchMyVouchers(),
    ]).then((results) => {
      if (cancelled) return;

      const [reviewsResult, appointmentsResult, trainingsResult, auditsResult, vouchersResult] = results;
      const pendingReviews = reviewsResult.status === 'fulfilled' ? reviewsResult.value : [];
      const appointments = appointmentsResult.status === 'fulfilled' ? appointmentsResult.value.upcoming ?? [] : [];
      const trainings = trainingsResult.status === 'fulfilled' ? trainingsResult.value : [];
      const audits = auditsResult.status === 'fulfilled' ? auditsResult.value : [];
      const vouchers = vouchersResult.status === 'fulfilled' ? vouchersResult.value : [];

      const now = Date.now();
      const nextAppointment = appointments[0] ?? null;
      const nextTraining = getNextTraining(trainings, now);
      const activeAudit = audits.find((audit) => audit.status !== 'done') ?? audits[0] ?? null;
      const usableVouchers = vouchers.filter((voucher) => isVoucherUsable(voucher, now));

      setNotifications([
        pendingReviews.length > 0 ? {
          key: `reviews:${pendingReviews.map((review) => review.orderItemId).sort((left, right) => left - right).join(',')}`,
          label: `${pendingReviews.length} avis produit${pendingReviews.length > 1 ? 's' : ''} à laisser`,
          to: pendingReviews[0] ? `/orders/${pendingReviews[0].orderId}` : '/orders/me',
        } : null,
        nextAppointment ? {
          key: `appointment:${nextAppointment.id}:${nextAppointment.startAt}`,
          label: `Prochain rendez-vous le ${formatOptionalFrenchDateTime(nextAppointment.startAt)}`,
          to: '/appointments/me',
        } : null,
        nextTraining ? {
          key: `training:${nextTraining.id}:${nextTraining.scheduledStartsAt}`,
          label: `Formation ${nextTraining.session.training.title} le ${formatOptionalFrenchDateTime(nextTraining.scheduledStartsAt)}`,
          to: `/trainings/me/${nextTraining.id}`,
        } : null,
        activeAudit ? {
          key: `audit:${activeAudit.id}:${activeAudit.status}`,
          label: `Audit ${AUDIT_STATUS_LABELS[activeAudit.status] ?? activeAudit.status} en suivi`,
          to: `/audits/me/${activeAudit.id}`,
        } : null,
        usableVouchers.length > 0 ? {
          key: `vouchers:${usableVouchers.map((voucher) => voucher.id).sort((left, right) => left - right).join(',')}`,
          label: `${usableVouchers.length} bon${usableVouchers.length > 1 ? 's' : ''} disponible${usableVouchers.length > 1 ? 's' : ''}`,
          to: '/vouchers/me',
        } : null,
      ].filter(Boolean) as AccountNotificationItem[]);
      setHasPartialError(results.some((result) => result.status === 'rejected'));
      setLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    let cancelled = false;
    setReadStateLoading(true);

    void fetchAccountNotificationsReadState()
      .then((nextReadState) => {
        if (!cancelled) {
          setReadState(nextReadState);
          setReadStateLoading(false);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setReadState(emptyReadState);
          setReadStateLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const seenKeys = useMemo(() => new Set(readState.seenKeys), [readState.seenKeys]);
  const dismissedKeys = useMemo(() => new Set(readState.dismissedKeys), [readState.dismissedKeys]);
  const availableNotifications = useMemo(
    () => notifications.filter((notification) => !dismissedKeys.has(notification.key)),
    [dismissedKeys, notifications],
  );
  const visibleNotifications = useMemo(() => {
    if (readStateLoading) return availableNotifications;

    let visibleUnreadCount = 0;
    return availableNotifications.filter((notification) => {
      if (seenKeys.has(notification.key)) return true;
      if (visibleUnreadCount >= MAX_VISIBLE_UNREAD_NOTIFICATIONS) return false;

      visibleUnreadCount += 1;
      return true;
    });
  }, [availableNotifications, readStateLoading, seenKeys]);
  const notificationCount = availableNotifications.length;
  const unreadCount = readStateLoading
    ? 0
    : availableNotifications.filter((notification) => !seenKeys.has(notification.key)).length;

  const markNotificationsAsSeen = useCallback(async (keys: string[]) => {
    const nextKeys = keys.filter((key) => !seenKeys.has(key));
    if (readStateLoading || nextKeys.length === 0) return;

    setReadState((current) => ({
      ...current,
      seenKeys: Array.from(new Set([...current.seenKeys, ...nextKeys])),
    }));
    try {
      const nextReadState = await markAccountNotificationsSeen(nextKeys);
      setReadState(nextReadState);
    } catch {
      // Keep the current page coherent; the server state will be retried on the next opening.
    }
  }, [readStateLoading, seenKeys]);

  const markCurrentNotificationsAsSeen = useCallback(() => {
    if (loading || readStateLoading) return false;

    const visibleUnreadKeys = visibleNotifications
      .filter((notification) => !seenKeys.has(notification.key))
      .map((notification) => notification.key);

    if (visibleUnreadKeys.length > 0) {
      void markNotificationsAsSeen(visibleUnreadKeys);
    }

    return true;
  }, [loading, markNotificationsAsSeen, readStateLoading, seenKeys, visibleNotifications]);

  const handleNotificationClick = useCallback((notificationKey: string) => {
    void markNotificationsAsSeen([notificationKey]);
  }, [markNotificationsAsSeen]);

  const handleDismissNotification = useCallback(async (notificationKey: string) => {
    setReadState((current) => ({
      ...current,
      dismissedKeys: Array.from(new Set([...current.dismissedKeys, notificationKey])),
      seenKeys: Array.from(new Set([...current.seenKeys, notificationKey])),
    }));

    try {
      const nextReadState = await dismissAccountNotification(notificationKey);
      setReadState(nextReadState);
    } catch {
      setReadState((current) => ({
        ...current,
        dismissedKeys: current.dismissedKeys.filter((key) => key !== notificationKey),
      }));
    }
  }, []);

  const buttonLabel = useMemo(() => {
    if (loading || readStateLoading) return 'Notifications en cours de chargement';
    if (notificationCount === 0) return 'Aucune notification';
    if (unreadCount === 0) return 'Notifications, aucune nouvelle';
    return `${unreadCount} notification${unreadCount > 1 ? 's' : ''} non lue${unreadCount > 1 ? 's' : ''}`;
  }, [loading, notificationCount, readStateLoading, unreadCount]);

  return (
    <Popover className="site-header__notifications">
      {({ open }) => (
        <AccountNotificationsPopoverContent
          buttonLabel={buttonLabel}
          hasPartialError={hasPartialError}
          loading={loading}
          markCurrentNotificationsAsSeen={markCurrentNotificationsAsSeen}
          notifications={visibleNotifications}
          onDismissNotification={handleDismissNotification}
          onNotificationClick={handleNotificationClick}
          open={open}
          seenKeys={seenKeys}
          unreadCount={unreadCount}
        />
      )}
    </Popover>
  );
};

interface AccountNotificationsPopoverContentProps {
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

const AccountNotificationsPopoverContent = ({
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
}: AccountNotificationsPopoverContentProps) => {
  const hasMarkedCurrentOpeningRef = useRef(false);

  useEffect(() => {
    if (!open) {
      hasMarkedCurrentOpeningRef.current = false;
      return;
    }

    if (hasMarkedCurrentOpeningRef.current) return;

    if (markCurrentNotificationsAsSeen()) {
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
                Supprimer
              </button>
            </div>
          ))
        )}
        {hasPartialError ? (
          <p className="site-header__notifications-warning">
            Certaines notifications n'ont pas pu être chargées.
          </p>
        ) : null}
      </PopoverPanel>
    </>
  );
};
