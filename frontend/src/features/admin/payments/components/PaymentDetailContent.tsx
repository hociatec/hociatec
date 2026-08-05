import { Link } from 'react-router';

import type {
  AdminPaymentDetailDto,
  AdminPaymentLiveStripeDto,
} from '@/features/orders/publicApi';
import { formatCurrencyCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type PaymentDetailContentProps = {
  payment: AdminPaymentDetailDto;
  liveStripe: AdminPaymentLiveStripeDto | null;
};

export const PaymentDetailContent = ({
  payment,
  liveStripe,
}: PaymentDetailContentProps) => {
  const liveFailureCode =
    liveStripe?.paymentIntent?.lastPaymentError?.declineCode ||
    liveStripe?.paymentIntent?.lastPaymentError?.code ||
    payment.failureCode ||
    null;
  const liveFailureMessage =
    liveStripe?.paymentIntent?.lastPaymentError?.message ||
    payment.failureMessage ||
    payment.failureCodeLabel || liveFailureCode;
  const liveFailureType =
    liveStripe?.paymentIntent?.lastPaymentError?.type ||
    (payment.failureCode ? 'card_error' : null);

  return (
    <div className="space-y-6">
      <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div>
            <div className="muted">Statut</div>
            <div className="font-semibold">
              {payment.statusLabel}
            </div>
          </div>
          <div>
            <div className="muted">Montant</div>
            <div className="font-semibold">
              {formatCurrencyCents(payment.totalPriceCents, payment.currencyCode)}
            </div>
          </div>
          <div>
            <div className="muted">Créé le</div>
            <div>{formatOptionalFrenchDateTime(payment.createdAt)}</div>
          </div>
          <div>
            <div className="muted">Commande liée</div>
            <div>
              {payment.orderId ? (
                <Link
                  to={`/admin/orders/${payment.orderId}`}
                  className="catalog-admin-table__primary-link"
                >
                  Ouvrir la commande
                </Link>
              ) : (
                '-'
              )}
            </div>
          </div>
        </div>
      </section>

      <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
        <div className="mb-4">
          <h2 className="text-lg font-semibold text-brand-900">Données internes</h2>
        </div>
        <div className="space-y-2 text-sm text-stone-700">
          <div><span className="font-medium text-brand-900">Client</span> : {payment.customerFullName || '-'} ({payment.customerEmail})</div>
          <div><span className="font-medium text-brand-900">Session Stripe</span> : {payment.stripeSessionId}</div>
          <div><span className="font-medium text-brand-900">PaymentIntent</span> : {payment.stripePaymentIntentId || '-'}</div>
          <div><span className="font-medium text-brand-900">Statut Stripe</span> : {payment.stripePaymentStatusLabel}</div>
          <div><span className="font-medium text-brand-900">Dernier événement Stripe</span> : {payment.lastStripeEventLabel}</div>
          <div><span className="font-medium text-brand-900">Expiré le</span> : {formatOptionalFrenchDateTime(payment.expiresAt)}</div>
          <div><span className="font-medium text-brand-900">Complété le</span> : {formatOptionalFrenchDateTime(payment.completedAt)}</div>
          <div><span className="font-medium text-brand-900">Motif d’échec</span> : {payment.failureMessage || payment.failureCodeLabel}</div>
          <div><span className="font-medium text-brand-900">Code d’échec</span> : {payment.failureCode || '-'}</div>
        </div>
      </section>

      <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
        <div className="mb-4">
          <h2 className="text-lg font-semibold text-brand-900">Retour Stripe en temps réel</h2>
        </div>
        {liveStripe?.error ? (
          <div className="text-sm text-amber-700">{liveStripe.error}</div>
        ) : (
          <div className="space-y-4 text-sm text-stone-700">
            <div><span className="font-medium text-brand-900">Session</span> : {liveStripe?.checkoutSession?.statusLabel || liveStripe?.checkoutSession?.status || '-'}</div>
            <div><span className="font-medium text-brand-900">Paiement</span> : {liveStripe?.checkoutSession?.paymentStatusLabel}</div>
            <div><span className="font-medium text-brand-900">PaymentIntent</span> : {liveStripe?.paymentIntent?.statusLabel}</div>
            <div><span className="font-medium text-brand-900">Message de refus</span> : {liveFailureMessage}</div>
            <div><span className="font-medium text-brand-900">Code de refus</span> : {liveFailureCode || '-'}</div>
            <div><span className="font-medium text-brand-900">Type d’erreur</span> : {liveFailureType || '-'}</div>
          </div>
        )}
      </section>
    </div>
  );
};
