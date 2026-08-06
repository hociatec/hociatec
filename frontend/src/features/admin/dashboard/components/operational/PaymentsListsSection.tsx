import { CircleAlert, CircleCheckBig } from 'lucide-react';

import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { DashboardCardAction, PanelTitle } from './AdminDashboardShared';

export const PaymentsListsSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="grid gap-6 xl:grid-cols-2">
    <PaymentsPanel
      title="Paiements à surveiller"
      helper="Échecs, expirations et paiements sans commande liée."
      payments={dashboard.payments.attention}
      attention
    />
    <PaymentsPanel
      title="Derniers paiements"
      helper="Accès rapide aux dernières sessions de paiement."
      payments={dashboard.payments.recent}
    />
  </section>
);

const PaymentsPanel = ({
  attention = false,
  helper,
  payments,
  title,
}: {
  attention?: boolean;
  helper: string;
  payments: AdminDashboardDto['payments']['attention'];
  title: string;
}) => {
  const paymentsPagination = useAdminPagination(payments);

  return (
  <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
    <PanelTitle
      title={title}
      helper={helper}
      to="/admin/payments"
      linkLabel={attention ? 'Ouvrir le module' : 'Tous les paiements'}
    />
    <div className="space-y-3">
      {payments.length === 0 ? (
        <div className="rounded-2xl bg-brand-900/40 p-4 text-sm text-stone-500">
          Aucun paiement critique à traiter.
        </div>
      ) : (
        paymentsPagination.paginatedItems.map((payment) => (
          <article
            key={payment.id}
            className="flex flex-col gap-2 rounded-2xl bg-brand-900/40 p-4 md:flex-row md:items-center md:justify-between"
          >
            <div className="min-w-0">
              <div className="flex items-center gap-2 font-semibold text-white">
                {attention ? (
                  <CircleAlert className="h-4 w-4 text-amber-300" />
                ) : (
                  <CircleCheckBig className="h-4 w-4 text-emerald-300" />
                )}
                <span>{payment.customerFullName || payment.customerEmail}</span>
              </div>
              <div className="text-sm text-stone-500">
                {payment.statusLabel}
                {' · '}
                {attention
                  ? (payment.stripePaymentStatusLabel ??
                    payment.stripePaymentStatusLabel)
                  : payment.orderId
                    ? `Commande #${payment.orderId}`
                    : 'Aucune commande liée'}
              </div>
              {attention && (
                <div className="text-xs text-stone-400">
                  {payment.failureMessage ||
                    (payment.failureCode
                      ? payment.failureCodeLabel
                      : payment.orderId === null && payment.status === 'paid'
                        ? 'Paiement confirmé sans commande liée.'
                        : 'À contrôler')}
                </div>
              )}
            </div>
            <div className="flex flex-col items-start gap-2 md:items-end">
              <div className="text-sm font-semibold text-white">
                {formatEuroCents(payment.totalPriceCents)}
              </div>
              <div className="text-xs text-stone-400">{formatFrenchDateTime(payment.createdAt)}</div>
              <DashboardCardAction to={`/admin/payments/${payment.id}`} label="Ouvrir" />
            </div>
          </article>
        ))
      )}
    </div>
    <PaginationControls
      className="mt-6 text-stone-300"
      page={paymentsPagination.page}
      total={paymentsPagination.total}
      totalLabel="paiement"
      totalPages={paymentsPagination.totalPages}
      onPageChange={paymentsPagination.setPage}
    />
  </div>
  );
};
