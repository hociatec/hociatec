import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  fetchAdminPaymentById,
  formatPaymentStatusFr,
  formatStripeEventTypeFr,
  formatStripePaymentStatusFr,
  type AdminPaymentDetailDto,
  type AdminPaymentLiveStripeDto,
} from '@/features/orders/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const formatPrice = (cents: number, currency = 'EUR') =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format((cents ?? 0) / 100);

const formatDateTime = (value?: string | null) =>
  value ? new Date(value).toLocaleString('fr-FR') : '-';

export const PaymentDetailPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const paymentId = Number(params.paymentId);
  const [payment, setPayment] = useState<AdminPaymentDetailDto | null>(null);
  const [liveStripe, setLiveStripe] = useState<AdminPaymentLiveStripeDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useDocumentTitle(payment ? `Admin - Paiement ${payment.id}` : 'Admin - Paiement');

  useEffect(() => {
    if (!paymentId) {
      setError('Paiement invalide.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);
    void fetchAdminPaymentById(paymentId)
      .then((data) => {
        setPayment(data.payment);
        setLiveStripe(data.liveStripe);
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Impossible de charger le paiement.'))
      .finally(() => setLoading(false));
  }, [paymentId]);

  return (
    <PageContainer
      title={payment ? `Paiement #${payment.id}` : 'Paiement'}
      headerActions={
        <button type="button" className="underline text-sm" onClick={() => navigate('/admin/payments')}>
          Retour aux paiements
        </button>
      }
    >
      {loading ? <p className="muted">Chargement...</p> : null}
      {error ? <div className="register-form__alert">{error}</div> : null}

      {payment ? (
        <div className="space-y-6">
          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
              <div>
                <div className="muted">Statut</div>
                <div className="font-semibold">{payment.statusLabel ?? formatPaymentStatusFr(payment.status)}</div>
              </div>
              <div>
                <div className="muted">Montant</div>
                <div className="font-semibold">{formatPrice(payment.totalPriceCents, payment.currencyCode)}</div>
              </div>
              <div>
                <div className="muted">Créé le</div>
                <div>{formatDateTime(payment.createdAt)}</div>
              </div>
              <div>
                <div className="muted">Commande liée</div>
                <div>
                  {payment.orderId ? (
                    <Link to={`/admin/orders/${payment.orderId}`} className="catalog-admin-table__primary-link">
                      Ouvrir la commande
                    </Link>
                  ) : (
                    '-'
                  )}
                </div>
              </div>
            </div>
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Données internes</h2>
            </div>
            <div className="space-y-2 text-sm text-slate-700">
              <div><span className="font-medium text-slate-900">Client</span> : {payment.customerFullName || '-'} ({payment.customerEmail})</div>
              <div><span className="font-medium text-slate-900">Session Stripe</span> : {payment.stripeSessionId}</div>
              <div><span className="font-medium text-slate-900">PaymentIntent</span> : {payment.stripePaymentIntentId || '-'}</div>
              <div><span className="font-medium text-slate-900">Statut Stripe</span> : {payment.stripePaymentStatusLabel ?? formatStripePaymentStatusFr(payment.stripePaymentStatus)}</div>
              <div><span className="font-medium text-slate-900">Dernier événement Stripe</span> : {payment.lastStripeEventLabel ?? formatStripeEventTypeFr(payment.lastStripeEventType)}</div>
              <div><span className="font-medium text-slate-900">Expiré le</span> : {formatDateTime(payment.expiresAt)}</div>
              <div><span className="font-medium text-slate-900">Complété le</span> : {formatDateTime(payment.completedAt)}</div>
              <div><span className="font-medium text-slate-900">Motif d’échec</span> : {payment.failureMessage || '-'}</div>
              <div><span className="font-medium text-slate-900">Code d’échec</span> : {payment.failureCode || '-'}</div>
            </div>
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Retour Stripe en temps réel</h2>
            </div>
            {liveStripe?.error ? (
              <div className="text-sm text-amber-700">{liveStripe.error}</div>
            ) : (
              <div className="space-y-4 text-sm text-slate-700">
                <div><span className="font-medium text-slate-900">Session</span> : {liveStripe?.checkoutSession?.statusLabel || liveStripe?.checkoutSession?.status || '-'}</div>
                <div><span className="font-medium text-slate-900">Paiement</span> : {liveStripe?.checkoutSession?.paymentStatusLabel ?? formatStripePaymentStatusFr(liveStripe?.checkoutSession?.paymentStatus)}</div>
                <div><span className="font-medium text-slate-900">PaymentIntent</span> : {liveStripe?.paymentIntent?.statusLabel ?? formatStripePaymentStatusFr(liveStripe?.paymentIntent?.status)}</div>
                <div><span className="font-medium text-slate-900">Message de refus</span> : {liveStripe?.paymentIntent?.lastPaymentError?.message || '-'}</div>
                <div><span className="font-medium text-slate-900">Code de refus</span> : {liveStripe?.paymentIntent?.lastPaymentError?.declineCode || liveStripe?.paymentIntent?.lastPaymentError?.code || '-'}</div>
                <div><span className="font-medium text-slate-900">Type d’erreur</span> : {liveStripe?.paymentIntent?.lastPaymentError?.type || '-'}</div>
              </div>
            )}
          </section>
        </div>
      ) : null}
    </PageContainer>
  );
};
