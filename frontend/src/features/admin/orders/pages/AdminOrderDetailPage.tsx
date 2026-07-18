import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { PageContainer } from '@/shared/components/PageContainer';
import {
  formatPaymentStatusFr,
  buildOrderInvoiceFilename,
  downloadOrderInvoicePdf,
  downloadOrderInvoiceXml,
  fetchAdminOrderById,
  formatOrderStatusFr,
  formatStripeEventTypeFr,
  formatStripePaymentStatusFr,
  resendAdminOrderEmail,
  retryAdminOrderInvoice,
  updateAdminOrderDelivery,
  type OrderDto,
  type OrderEventDto,
  type OrderProcessingDto,
} from '@/features/orders/api';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

const formatDateTime = (value?: string | null) =>
  value ? new Date(value).toLocaleString('fr-FR') : 'Non envoyé';

const toDateInputValue = (value?: string | null) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toISOString().slice(0, 10);
};

export const AdminOrderDetailPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const orderId = Number(params.orderId);
  const [order, setOrder] = useState<OrderDto | null>(null);
  const [events, setEvents] = useState<OrderEventDto[]>([]);
  const [processing, setProcessing] = useState<OrderProcessingDto | null>(null);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [actionMessage, setActionMessage] = useState<string | null>(null);
  const [deliverySaving, setDeliverySaving] = useState(false);
  const [deliveryForm, setDeliveryForm] = useState({
    status: 'preparing',
    carrier: '',
    trackingNumber: '',
    trackingUrl: '',
    estimatedAt: '',
  });

  useEffect(() => {
    if (!orderId) {
      setStatus('error');
      setError('Commande invalide.');
      return;
    }

    setStatus('loading');
    setError(null);
    setActionMessage(null);
    void fetchAdminOrderById(orderId)
      .then((data) => {
        setOrder(data.order);
        setEvents(data.events);
        setProcessing(data.processing);
        setDeliveryForm({
          status: data.order.delivery?.status ?? 'preparing',
          carrier: data.order.delivery?.carrier ?? '',
          trackingNumber: data.order.delivery?.trackingNumber ?? '',
          trackingUrl: data.order.delivery?.trackingUrl ?? '',
          estimatedAt: toDateInputValue(data.order.delivery?.estimatedAt),
        });
        setStatus('success');
      })
      .catch((e: unknown) => {
        setStatus('error');
        setError(e instanceof Error ? e.message : 'Impossible de charger la commande.');
      });
  }, [orderId]);

  const reload = async () => {
    const data = await fetchAdminOrderById(orderId);
    setOrder(data.order);
    setEvents(data.events);
    setProcessing(data.processing);
    setDeliveryForm({
      status: data.order.delivery?.status ?? 'preparing',
      carrier: data.order.delivery?.carrier ?? '',
      trackingNumber: data.order.delivery?.trackingNumber ?? '',
      trackingUrl: data.order.delivery?.trackingUrl ?? '',
      estimatedAt: toDateInputValue(data.order.delivery?.estimatedAt),
    });
  };

  return (
    <PageContainer
      title={order ? `Commande ${order.number}` : 'Commande'}
      headerActions={
        <button type="button" className="underline text-sm" onClick={() => navigate('/admin/orders')}>
          Retour aux commandes
        </button>
      }
    >
      {status === 'loading' && <p>Chargement...</p>}
      {error && <div className="register-form__alert">{error}</div>}
      {actionMessage && <div className="rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800">{actionMessage}</div>}

      {status === 'success' && order && processing && (
        <div className="space-y-6">
          <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 bg-slate-950 px-6 py-5 text-white">
              <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Commande</p>
                  <h2 className="mt-1 text-2xl font-semibold">{order.number}</h2>
                  <p className="mt-2 text-sm text-slate-300">
                    Créée le {new Date(order.createdAt).toLocaleString('fr-FR')} pour{' '}
                    {order.customerDisplayName || order.invoice?.billingName || order.shipping.name || 'Client inconnu'}.
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                  <span className="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-white">
                    {order.statusLabel ?? formatOrderStatusFr(order.status)}
                  </span>
                  <span className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900">
                    {formatPrice(order.totalPriceCents)}
                  </span>
                </div>
              </div>
            </div>

            <div className="grid gap-4 px-6 py-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
              <div className="rounded-3xl border border-slate-200 p-5">
                <div className="text-sm font-semibold text-slate-900">Client et facturation</div>
                <div className="mt-3 font-semibold text-slate-900">{order.customerDisplayName || order.invoice?.billingName || order.shipping.name || 'Client inconnu'}</div>
                {order.invoice?.billingCompany ? <div className="mt-1 text-sm text-slate-600">{order.invoice.billingCompany}</div> : null}
                {order.invoice?.billingEmail ? <div className="text-sm text-slate-600">{order.invoice.billingEmail}</div> : null}
                <div className="mt-4 grid gap-3 text-sm text-slate-600">
                  <div>
                    <span className="font-medium text-slate-900">Statut</span> : {order.statusLabel ?? formatOrderStatusFr(order.status)}
                  </div>
                  <div>
                    <span className="font-medium text-slate-900">Date</span> : {new Date(order.createdAt).toLocaleString('fr-FR')}
                  </div>
                  {order.invoice?.number ? (
                    <div>
                    <span className="font-medium text-slate-900">Facture</span> : {order.invoice.number}
                  </div>
                  ) : null}
                  {order.payment ? (
                    <div>
                      <span className="font-medium text-slate-900">Paiement</span> : {order.payment.statusLabel ?? formatPaymentStatusFr(order.payment.status)}
                    </div>
                  ) : null}
                </div>
              </div>

              <div className="rounded-3xl border border-slate-200 p-5">
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div>
                    <div className="text-sm font-semibold text-slate-900">Traitements automatiques</div>
                    <p className="mt-1 text-sm text-slate-500">Vérification rapide de la facture et des e-mails liés à la commande.</p>
                  </div>
                </div>
                {order.hasIssues && (order.issueReasons?.length ?? 0) > 0 ? (
                  <div className="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div className="text-sm font-semibold text-amber-950">Anomalies détectées</div>
                    <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-900">
                      {order.issueReasons?.map((reason) => (
                        <li key={reason}>{reason}</li>
                      ))}
                    </ul>
                  </div>
                ) : null}
                <ul className="mt-4 space-y-2 text-sm text-slate-700">
                <li>Facture PDF: {processing.invoicePdfGenerated ? 'générée' : 'manquante'}</li>
                <li>Facture XML: {processing.invoiceXmlGenerated ? 'générée' : 'manquante'}</li>
                <li>Email commande: {formatDateTime(processing.orderCreatedEmailSentAt)}</li>
                <li>Email livraison: {formatDateTime(processing.statusDeliveredEmailSentAt)}</li>
                <li>Email annulation: {formatDateTime(processing.statusCancelledEmailSentAt)}</li>
              </ul>
              <div className="mt-5 flex flex-wrap gap-3 text-sm">
                <button
                  type="button"
                  className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 font-semibold text-slate-700 transition hover:border-slate-500"
                  onClick={() => {
                    setActionMessage(null);
                    setError(null);
                    void retryAdminOrderInvoice(order.id)
                      .then(async () => {
                        await reload();
                        setActionMessage('Facture regénérée.');
                      })
                      .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Impossible de regénérer la facture.'));
                  }}
                >
                  Regénérer la facture
                </button>
                <button
                  type="button"
                  className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 font-semibold text-slate-700 transition hover:border-slate-500"
                  onClick={() => {
                    setActionMessage(null);
                    setError(null);
                    void resendAdminOrderEmail(order.id, 'order_created')
                      .then(async () => {
                        await reload();
                        setActionMessage('Email de commande renvoyé.');
                      })
                      .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Impossible de renvoyer l’email.'));
                  }}
                >
                  Renvoyer email commande
                </button>
                {(order.status === 'delivered' || order.status === 'cancelled') ? (
                  <button
                    type="button"
                    className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 font-semibold text-slate-700 transition hover:border-slate-500"
                    onClick={() => {
                      setActionMessage(null);
                      setError(null);
                      void resendAdminOrderEmail(order.id, 'current_status')
                        .then(async () => {
                          await reload();
                          setActionMessage('Email de statut renvoyé.');
                        })
                        .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Impossible de renvoyer l’email.'));
                    }}
                  >
                    Renvoyer email statut
                  </button>
                ) : null}
              </div>
              {order.invoice?.number ? (
                <div className="mt-4 flex flex-wrap gap-3 text-sm">
                  <button
                    type="button"
                    className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 font-semibold text-white transition hover:bg-slate-800"
                    onClick={() => void downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order))}
                  >
                    Télécharger la facture PDF
                  </button>
                  <button
                    type="button"
                    className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 font-semibold text-slate-700 transition hover:border-slate-500"
                    onClick={() => void downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order))}
                  >
                    Télécharger la facture XML
                  </button>
                </div>
              ) : null}
              </div>
            </div>
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Paiement</h2>
              <p className="mt-1 text-sm text-slate-500">Contrôle rapide pour savoir si la commande a été payée et ouvrir la fiche paiement.</p>
            </div>
            {order.payment ? (
              <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div className="space-y-2 text-sm text-slate-700">
                  <div><span className="font-medium text-slate-900">Statut</span> : {order.payment.statusLabel ?? formatPaymentStatusFr(order.payment.status)}</div>
                  <div><span className="font-medium text-slate-900">Statut Stripe</span> : {order.payment.stripePaymentStatusLabel ?? formatStripePaymentStatusFr(order.payment.stripePaymentStatus)}</div>
                  <div><span className="font-medium text-slate-900">Dernier événement Stripe</span> : {order.payment.lastStripeEventLabel ?? formatStripeEventTypeFr(order.payment.lastStripeEventType)}</div>
                  <div><span className="font-medium text-slate-900">Paiement confirmé le</span> : {order.payment.completedAt ? new Date(order.payment.completedAt).toLocaleString('fr-FR') : '-'}</div>
                  <div><span className="font-medium text-slate-900">Session expirée le</span> : {order.payment.expiresAt ? new Date(order.payment.expiresAt).toLocaleString('fr-FR') : '-'}</div>
                  <div><span className="font-medium text-slate-900">Motif d’échec</span> : {order.payment.failureMessage || '-'}</div>
                </div>
                <div className="flex items-start">
                  <Link
                    className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                    to={`/admin/payments/${order.payment.id}`}
                  >
                    Ouvrir la fiche paiement
                  </Link>
                </div>
              </div>
            ) : (
              <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-600">
                Aucun paiement lié à cette commande.
              </div>
            )}
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Livraison</h2>
              <p className="mt-1 text-sm text-slate-500">Informations de suivi visibles aussi côté client.</p>
            </div>
            <div className="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
              <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-700">
                <div><span className="font-medium text-slate-900">Étape</span> : {order.delivery?.statusLabel ?? 'Préparation en cours'}</div>
                <div><span className="font-medium text-slate-900">Transporteur</span> : {order.delivery?.carrier || '-'}</div>
                <div><span className="font-medium text-slate-900">Numéro de suivi</span> : {order.delivery?.trackingNumber || '-'}</div>
                <div><span className="font-medium text-slate-900">Date estimée</span> : {order.delivery?.estimatedAt ? new Date(order.delivery.estimatedAt).toLocaleDateString('fr-FR') : '-'}</div>
                <div><span className="font-medium text-slate-900">Expédiée le</span> : {order.delivery?.shippedAt ? new Date(order.delivery.shippedAt).toLocaleString('fr-FR') : '-'}</div>
                <div><span className="font-medium text-slate-900">Livrée le</span> : {order.delivery?.deliveredAt ? new Date(order.delivery.deliveredAt).toLocaleString('fr-FR') : '-'}</div>
                {order.delivery?.trackingUrl ? (
                  <div className="mt-3">
                    <a className="text-sky-700 underline" href={order.delivery.trackingUrl} target="_blank" rel="noreferrer">
                      Ouvrir le lien de suivi
                    </a>
                  </div>
                ) : null}
              </div>
              <div className="space-y-3 rounded-2xl border border-slate-200 p-4">
                <label className="flex flex-col gap-1 text-sm">
                  <span className="font-medium text-slate-900">Étape</span>
                  <select value={deliveryForm.status} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, status: e.target.value }))}>
                    <option value="preparing">Préparation en cours</option>
                    <option value="shipped">Expédiée</option>
                    <option value="in_transit">En transit</option>
                    <option value="out_for_delivery">En cours de livraison</option>
                    <option value="delivered">Livrée</option>
                    <option value="issue">Incident de livraison</option>
                  </select>
                </label>
                <label className="flex flex-col gap-1 text-sm">
                  <span className="font-medium text-slate-900">Transporteur</span>
                  <input value={deliveryForm.carrier} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, carrier: e.target.value }))} placeholder="Colissimo, DHL..." />
                </label>
                <label className="flex flex-col gap-1 text-sm">
                  <span className="font-medium text-slate-900">Numéro de suivi</span>
                  <input value={deliveryForm.trackingNumber} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, trackingNumber: e.target.value }))} placeholder="Numéro de suivi" />
                </label>
                <label className="flex flex-col gap-1 text-sm">
                  <span className="font-medium text-slate-900">Lien de suivi</span>
                  <input value={deliveryForm.trackingUrl} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, trackingUrl: e.target.value }))} placeholder="https://..." />
                </label>
                <label className="flex flex-col gap-1 text-sm">
                  <span className="font-medium text-slate-900">Date estimée</span>
                  <input type="date" value={deliveryForm.estimatedAt} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, estimatedAt: e.target.value }))} />
                </label>
                <div>
                  <button
                    type="button"
                    className="register-form__submit"
                    disabled={deliverySaving}
                    onClick={() => {
                      setDeliverySaving(true);
                      setActionMessage(null);
                      setError(null);
                      void updateAdminOrderDelivery(order.id, deliveryForm)
                        .then(async () => {
                          await reload();
                          setActionMessage('Suivi livraison mis à jour.');
                        })
                        .catch((e: unknown) => setError(e instanceof Error ? e.message : 'Impossible de mettre à jour la livraison.'))
                        .finally(() => setDeliverySaving(false));
                    }}
                  >
                    {deliverySaving ? 'Enregistrement...' : 'Enregistrer le suivi'}
                  </button>
                </div>
              </div>
            </div>
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Articles</h2>
              <p className="mt-1 text-sm text-slate-500">Détail des produits, quantités et montants de cette commande.</p>
            </div>
            <div className="space-y-3">
              {order.items.map((item) => (
                <div key={item.orderItemId} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                  <div>
                    <div className="font-medium text-slate-900">{item.productName}</div>
                    <div className="text-sm text-slate-500">SKU {item.productSku} · Qté {item.quantity}</div>
                  </div>
                  <div className="text-sm font-semibold text-slate-800">{formatPrice(item.linePriceCents)}</div>
                </div>
              ))}
            </div>
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Historique</h2>
              <p className="mt-1 text-sm text-slate-500">Trace des actions et événements enregistrés sur la commande.</p>
            </div>
            {events.length === 0 ? (
              <p className="text-sm text-slate-500">Aucun événement enregistré.</p>
            ) : (
              <ul className="space-y-2 text-sm text-slate-700">
                {events.map((event) => (
                  <li key={event.id} className="rounded-xl bg-slate-50 px-3 py-2">
                    <div className="text-xs text-slate-500">{new Date(event.createdAt).toLocaleString('fr-FR')}</div>
                    <div>{event.message || event.type}</div>
                    {event.actor?.name ? <div className="text-xs text-slate-500">Par {event.actor.name}</div> : null}
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4">
              <h2 className="text-lg font-semibold text-slate-900">Accès client</h2>
              <p className="mt-1 text-sm text-slate-500">Raccourcis utiles pour ouvrir le client ou vérifier sa vue commande.</p>
            </div>
            <div className="flex flex-wrap gap-4">
              {order.userId ? (
                <Link
                  className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                  to={`/admin/customers/${order.userId}`}
                >
                  Ouvrir la fiche client
                </Link>
              ) : null}
              <Link
                className="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-500"
                to={`/orders/${order.id}`}
              >
                Ouvrir la vue client de cette commande
              </Link>
            </div>
          </section>
        </div>
      )}
    </PageContainer>
  );
};
