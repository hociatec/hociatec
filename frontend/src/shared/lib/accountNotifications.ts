import type { AuditListItemDto } from '@/features/audits/api/auditsApi';
import type { TrainingEnrollmentDto } from '@/features/trainings/api/trainingsApi';
import type { MyVoucherDto } from '@/features/vouchers/api/vouchersApi';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import type { AccountNotificationItem } from '@/shared/types/accountNotifications';

const isVoucherUsable = (voucher: MyVoucherDto, now: number) => {
  if (!voucher.isActive) return false;
  if (voucher.startsAt && new Date(voucher.startsAt).getTime() > now) return false;
  if (voucher.endsAt && new Date(voucher.endsAt).getTime() < now) return false;
  return true;
};

const getNextTraining = (trainings: TrainingEnrollmentDto[], now: number) =>
  trainings
    .filter(
      (training) =>
        training.status !== 'cancelled' && new Date(training.scheduledStartsAt).getTime() >= now,
    )
    .sort(
      (left, right) =>
        new Date(left.scheduledStartsAt).getTime() - new Date(right.scheduledStartsAt).getTime(),
    )[0] ?? null;

interface NotificationSources {
  pendingReviews: Array<{ orderItemId: number; orderId: number }>;
  appointments: Array<{ id: number; startAt: string }>;
  trainings: TrainingEnrollmentDto[];
  audits: AuditListItemDto[];
  vouchers: MyVoucherDto[];
}

export const buildAccountNotifications = ({
  pendingReviews,
  appointments,
  trainings,
  audits,
  vouchers,
}: NotificationSources): AccountNotificationItem[] => {
  const now = Date.now();
  const nextAppointment = appointments[0] ?? null;
  const nextTraining = getNextTraining(trainings, now);
  const activeAudit = audits.find((audit) => audit.status !== 'done') ?? audits[0] ?? null;
  const usableVouchers = vouchers.filter((voucher) => isVoucherUsable(voucher, now));

  return [
    pendingReviews.length > 0
      ? {
          key: `reviews:${pendingReviews
            .map((review) => review.orderItemId)
            .sort((left, right) => left - right)
            .join(',')}`,
          label: `${pendingReviews.length} avis produit${pendingReviews.length > 1 ? 's' : ''} à laisser`,
          to: pendingReviews[0] ? `/orders/${pendingReviews[0].orderId}` : '/orders/me',
        }
      : null,
    nextAppointment
      ? {
          key: `appointment:${nextAppointment.id}:${nextAppointment.startAt}`,
          label: `Prochain rendez-vous le ${formatOptionalFrenchDateTime(nextAppointment.startAt)}`,
          to: '/appointments/me',
        }
      : null,
    nextTraining
      ? {
          key: `training:${nextTraining.id}:${nextTraining.scheduledStartsAt}`,
          label: `Formation ${nextTraining.session.training.title} le ${formatOptionalFrenchDateTime(nextTraining.scheduledStartsAt)}`,
          to: `/trainings/me/${nextTraining.id}`,
        }
      : null,
    activeAudit
      ? {
          key: `audit:${activeAudit.id}:${activeAudit.status}`,
          label: `Audit ${activeAudit.statusLabel} en suivi`,
          to: `/audits/me/${activeAudit.id}`,
        }
      : null,
    usableVouchers.length > 0
      ? {
          key: `vouchers:${usableVouchers
            .map((voucher) => voucher.id)
            .sort((left, right) => left - right)
            .join(',')}`,
          label: `${usableVouchers.length} bon${usableVouchers.length > 1 ? 's' : ''} disponible${usableVouchers.length > 1 ? 's' : ''}`,
          to: '/vouchers/me',
        }
      : null,
  ].filter((notification): notification is AccountNotificationItem => notification !== null);
};
