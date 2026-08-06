import type { DashboardAction, DashboardData } from '@/features/account/types/dashboard';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { parseNonNegativeInteger } from '@/shared/lib/parsers';

export const selectQuoteToAnswer = (data: DashboardData) =>
  data.quotes.find(
    (quote) => (quote.statusCode ?? quote.status) === 'sent' && !quote.convertedOrder,
  ) ?? null;

export const selectNextAppointment = (data: DashboardData) => data.appointments[0] ?? null;

export const selectNextTraining = (data: DashboardData, loadedAtMs: number) =>
  data.trainings
    .filter(
      (training) =>
        training.status !== 'cancelled' &&
        new Date(training.scheduledStartsAt).getTime() >= loadedAtMs,
    )
    .sort(
      (a, b) => new Date(a.scheduledStartsAt).getTime() - new Date(b.scheduledStartsAt).getTime(),
    )[0] ?? null;

export const selectDashboardActions = (
  data: DashboardData,
  loadedAtMs: number,
): DashboardAction[] => {
  const actions: DashboardAction[] = [];
  const firstReview = data.pendingReviews[0];
  const nextAppointment = selectNextAppointment(data);
  const quoteToAnswer = selectQuoteToAnswer(data);
  const nextTraining = selectNextTraining(data, loadedAtMs);

  if (data.pendingReviews.length > 0) {
    actions.push({
      kind: 'order',
      title: `Laisser ${data.pendingReviews.length} avis produit${data.pendingReviews.length > 1 ? 's' : ''}`,
      detail: firstReview ? `Commande ${firstReview.orderNumber}` : 'Commande à compléter',
      to: firstReview ? `/orders/${firstReview.orderId}` : '/orders/me',
    });
  }
  if (nextAppointment) {
    actions.push({
      kind: 'appointment',
      title: 'Préparer votre rendez-vous',
      detail: formatOptionalFrenchDateTime(nextAppointment.startAt),
      to: '/appointments/me',
    });
  }
  if (quoteToAnswer) {
    actions.push({
      kind: 'quote',
      title: `Répondre au devis ${quoteToAnswer.number ?? `#${quoteToAnswer.id}`}`,
      detail: 'Accepter ou refuser la proposition',
      to: `/quotes/me/${quoteToAnswer.id}`,
    });
  }
  if (nextTraining) {
    actions.push({
      kind: 'training',
      title: 'Voir votre formation à venir',
      detail: `${nextTraining.session.training.title} · ${formatOptionalFrenchDateTime(nextTraining.scheduledStartsAt)}`,
      to: `/trainings/me/${nextTraining.id}`,
    });
  }
  return actions;
};

export const normalizeConversionPoints = (value: string) =>
  Math.floor((parseNonNegativeInteger(value, 0) / 100)) * 100;

export const getDefaultConvertPoints = (points: number) => {
  if (points <= 0) return '0';
  return String(Math.floor(points / 100) * 100 || points);
};
