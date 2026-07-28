import { Link } from 'react-router';

import type { OrderDto } from '@/features/orders/api';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

export const AdminOrderPaymentSection = ({
  payment,
}: {
  payment: NonNullable<OrderDto['payment']> | null;
}) => (
  <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-brand-900">Paiement</h2>
      <p className="mt-1 text-sm text-stone-500">
        Contrôle rapide pour savoir si la commande a été payée et ouvrir la fiche paiement.
      </p>
    </div>
    {payment ? (
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div className="space-y-2 text-sm text-stone-700">
          <div><span className="font-medium text-brand-900">Statut</span> : {payment.statusLabel}</div>
          <div><span className="font-medium text-brand-900">Statut Stripe</span> : {payment.stripePaymentStatusLabel}</div>
          <div><span className="font-medium text-brand-900">Dernier événement Stripe</span> : {payment.lastStripeEventLabel}</div>
          <div><span className="font-medium text-brand-900">Paiement confirmé le</span> : {formatOptionalFrenchDateTime(payment.completedAt)}</div>
          <div><span className="font-medium text-brand-900">Session expirée le</span> : {formatOptionalFrenchDateTime(payment.expiresAt)}</div>
          <div><span className="font-medium text-brand-900">Motif d’échec</span> : {payment.failureMessage || payment.failureCodeLabel}</div>
        </div>
        <div className="flex items-start">
          <Link className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600" to={`/admin/payments/${payment.id}`}>
            Ouvrir la fiche paiement
          </Link>
        </div>
      </div>
    ) : (
      <div className="rounded-2xl border border-dashed border-brand-100 bg-brand-50 px-4 py-5 text-sm text-stone-600">
        Aucun paiement lié à cette commande.
      </div>
    )}
  </section>
);
