import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';

import { PageContainer } from '@/shared/components/PageContainer';
import {
  bulkUpdateOrderStatus,
  convertQuoteToOrder,
  createRefund,
  createStockMovement,
  createSupportRequest,
  fetchEmailLogs,
  fetchFulfillmentOrders,
  fetchOperationsOverview,
  fetchRefunds,
  fetchStockMovements,
  fetchSupportRequests,
  processStripeRefund,
  replySupportRequest,
  shipFulfillmentOrder,
  updateLowStockThreshold,
  updateRefund,
  updateSupportRequest,
  type EmailLogDto,
  type FulfillmentOrderDto,
  type OperationsOverviewDto,
  type RefundRequestDto,
  type StockMovementDto,
  type SupportRequestDto,
} from '@/features/admin/operations/api';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

const formatDate = (value: string) => new Date(value).toLocaleString('fr-FR');

const cardClass = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm';
const inputClass = 'w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100';
const primaryActionClass = 'rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50';
const secondaryActionClass = 'rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-brand-300 hover:text-brand-700';

const exportLabels: Record<string, string> = {
  orders: 'Commandes',
  customers: 'Clients',
  products: 'Produits',
  quotes: 'Devis',
  refunds: 'Remboursements',
  support: 'SAV',
};

export const AdminOperationsPage = () => {
  const navigate = useNavigate();
  const [overview, setOverview] = useState<OperationsOverviewDto | null>(null);
  const [support, setSupport] = useState<SupportRequestDto[]>([]);
  const [refunds, setRefunds] = useState<RefundRequestDto[]>([]);
  const [stock, setStock] = useState<StockMovementDto[]>([]);
  const [emails, setEmails] = useState<EmailLogDto[]>([]);
  const [fulfillmentOrders, setFulfillmentOrders] = useState<FulfillmentOrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [message, setMessage] = useState<string | null>(null);

  const [supportForm, setSupportForm] = useState({ customerId: '', orderId: '', subject: '', reason: 'other', message: '', internalNotes: '' });
  const [refundForm, setRefundForm] = useState({ orderId: '', amountCents: '', reason: '', internalNotes: '' });
  const [stockForm, setStockForm] = useState({ productId: '', delta: '', reason: 'adjustment', note: '' });
  const [bulkForm, setBulkForm] = useState({ orderIds: '', status: 'confirmed' });
  const [quoteReference, setQuoteReference] = useState('');
  const [quoteConversionStatus, setQuoteConversionStatus] = useState<'idle' | 'loading' | 'error'>('idle');
  const [quoteConversionMessage, setQuoteConversionMessage] = useState<string | null>(null);
  const [shippingForms, setShippingForms] = useState<Record<number, { carrier: string; trackingNumber: string; trackingUrl: string }>>({});
  const [supportReplies, setSupportReplies] = useState<Record<number, { subject: string; message: string }>>({});
  const [refundConfirmations, setRefundConfirmations] = useState<Record<number, string>>({});
  const [stockThresholds, setStockThresholds] = useState<Record<number, string>>({});

  const refresh = () => {
    setStatus('loading');
    setMessage(null);
    Promise.all([
      fetchOperationsOverview(),
      fetchSupportRequests(),
      fetchRefunds(),
      fetchStockMovements(),
      fetchEmailLogs(),
      fetchFulfillmentOrders(),
    ])
      .then(([overviewData, supportData, refundData, stockData, emailData, fulfillmentData]) => {
        setOverview(overviewData);
        setSupport(supportData);
        setRefunds(refundData);
        setStock(stockData);
        setEmails(emailData);
        setFulfillmentOrders(fulfillmentData);
        setStatus('success');
      })
      .catch((error: unknown) => {
        setMessage(error instanceof Error ? error.message : 'Erreur de chargement.');
        setStatus('error');
      });
  };

  useEffect(() => {
    refresh();
  }, []);

  const failedEmails = useMemo(() => emails.filter((email) => email.status === 'failed').length, [emails]);
  const hasPriorities = Boolean(
    (overview?.support.openCount ?? 0) > 0
    || (overview?.refunds.pendingCount ?? 0) > 0
    || (overview?.stock.lowStockCount ?? 0) > 0
    || failedEmails > 0,
  );

  const submitSupport = () => {
    void createSupportRequest({
      customerId: Number(supportForm.customerId),
      orderId: supportForm.orderId ? Number(supportForm.orderId) : null,
      subject: supportForm.subject || 'Demande SAV',
      reason: supportForm.reason,
      message: supportForm.message,
      internalNotes: supportForm.internalNotes,
    })
      .then(() => {
        setSupportForm({ customerId: '', orderId: '', subject: '', reason: 'other', message: '', internalNotes: '' });
        setMessage('Demande SAV créée.');
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur SAV.'));
  };

  const submitRefund = () => {
    void createRefund({
      orderId: Number(refundForm.orderId),
      amountCents: Number(refundForm.amountCents),
      reason: refundForm.reason,
      internalNotes: refundForm.internalNotes,
    })
      .then(() => {
        setRefundForm({ orderId: '', amountCents: '', reason: '', internalNotes: '' });
        setMessage('Suivi de remboursement créé.');
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur remboursement.'));
  };

  const submitStock = () => {
    void createStockMovement({
      productId: Number(stockForm.productId),
      delta: Number(stockForm.delta),
      reason: stockForm.reason,
      note: stockForm.note,
    })
      .then(() => {
        setStockForm({ productId: '', delta: '', reason: 'adjustment', note: '' });
        setMessage('Stock ajusté.');
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur stock.'));
  };

  const submitBulk = () => {
    const ids = bulkForm.orderIds.split(',').map((value) => Number(value.trim())).filter((value) => Number.isFinite(value) && value > 0);
    void bulkUpdateOrderStatus(ids, bulkForm.status)
      .then((updated) => {
        setMessage(`${updated} commande(s) mise(s) à jour.`);
        setBulkForm({ orderIds: '', status: 'confirmed' });
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur action groupée.'));
  };

  const submitQuoteConversion = () => {
    const reference = quoteReference.trim();
    if (reference === '') {
      setQuoteConversionStatus('error');
      setQuoteConversionMessage('Renseigne l’ID ou le numéro du devis.');
      return;
    }

    setQuoteConversionStatus('loading');
    setQuoteConversionMessage(null);
    void convertQuoteToOrder(reference)
      .then((order) => {
        setMessage(`Devis converti en commande ${order.number}.`);
        setQuoteReference('');
        setQuoteConversionStatus('idle');
        void navigate(`/admin/orders/${order.id}`);
      })
      .catch((error: unknown) => {
        setQuoteConversionStatus('error');
        setQuoteConversionMessage(error instanceof Error ? error.message : 'Erreur conversion devis.');
      });
  };

  const submitShipOrder = (orderId: number) => {
    const payload = shippingForms[orderId] ?? { carrier: '', trackingNumber: '', trackingUrl: '' };
    void shipFulfillmentOrder(orderId, payload)
      .then(() => {
        setMessage('Commande marquée comme expédiée.');
        setShippingForms((previous) => ({ ...previous, [orderId]: { carrier: '', trackingNumber: '', trackingUrl: '' } }));
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur expédition.'));
  };

  const submitSupportReply = (supportId: number) => {
    const payload = supportReplies[supportId] ?? { subject: `Réponse SAV #${supportId}`, message: '' };
    void replySupportRequest(supportId, { ...payload, status: 'waiting_customer' })
      .then(() => {
        setMessage('Réponse SAV envoyée au client.');
        setSupportReplies((previous) => ({ ...previous, [supportId]: { subject: '', message: '' } }));
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur réponse SAV.'));
  };

  const submitStripeRefund = (refundId: number) => {
    void processStripeRefund(refundId, { confirmation: refundConfirmations[refundId] ?? '' })
      .then((refund) => {
        setMessage(`Remboursement Stripe traité : ${refund.stripeRefundId ?? 'référence Stripe créée'}.`);
        setRefundConfirmations((previous) => ({ ...previous, [refundId]: '' }));
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur remboursement Stripe.'));
  };

  const submitStockThreshold = (productId: number) => {
    const threshold = Number(stockThresholds[productId]);
    void updateLowStockThreshold(productId, threshold)
      .then(() => {
        setMessage('Seuil de stock faible mis à jour.');
        refresh();
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur seuil stock.'));
  };

  return (
    <PageContainer title="Centre exploitation">
      <div className="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div className="max-w-3xl">
            <p className="text-sm font-semibold uppercase tracking-wide text-brand-700">Tableau de bord opérationnel</p>
            <h1 className="mt-2 text-2xl font-semibold text-slate-950">Ce qui demande une action aujourd’hui</h1>
            <p className="mt-2 text-sm leading-6 text-slate-600">
              Cette page regroupe les tâches de suivi : SAV, remboursements manuels, corrections de stock,
              exports CSV, emails transactionnels et actions groupées sur les commandes.
            </p>
          </div>
          <button className={secondaryActionClass} type="button" onClick={refresh}>
            Actualiser
          </button>
        </div>

        {message && (
          <div className="mt-4 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-900">
            {message}
          </div>
        )}
        {status === 'loading' && <p className="mt-4 text-sm text-slate-500">Chargement des données...</p>}
        {status === 'error' && <p className="mt-4 text-sm text-red-600">Certaines données n’ont pas pu être chargées.</p>}
      </div>

      {overview && (
        <section className="mb-8">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-lg font-semibold text-slate-950">Priorités</h2>
            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${hasPriorities ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>
              {hasPriorities ? 'À vérifier' : 'Aucune urgence'}
            </span>
          </div>
          <div className="grid gap-4 md:grid-cols-4">
            <StatCard label="SAV ouverts" value={overview.support.openCount} helper="Demandes client à traiter ou relancer." tone={overview.support.openCount > 0 ? 'warning' : 'neutral'} />
            <StatCard label="Remboursements" value={overview.refunds.pendingCount} helper="Suivis en attente de décision." tone={overview.refunds.pendingCount > 0 ? 'warning' : 'neutral'} />
            <StatCard
              label="Produits en stock faible"
              value={overview.stock.lowStockCount}
              helper={`Produits publiés avec ${overview.stock.lowStockThreshold ?? 3} unités ou moins.`}
              tone={overview.stock.lowStockCount > 0 ? 'warning' : 'neutral'}
            />
            <StatCard label="Emails échoués" value={failedEmails} helper="Emails transactionnels non envoyés." tone={failedEmails > 0 ? 'danger' : 'neutral'} />
          </div>
          <div className="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h3 className="font-semibold text-slate-950">Détail des stocks faibles</h3>
                <p className="mt-1 text-sm text-slate-500">
                  Le compteur correspond au nombre de produits publiés sous le seuil, pas au nombre d’unités restantes.
                </p>
              </div>
              <a className={secondaryActionClass + ' text-center'} href="/admin/catalog/products?stock=low">
                Voir tous les stocks faibles
              </a>
            </div>

            {(overview.stock.lowStockItems ?? []).length === 0 ? (
              <p className="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Aucun produit publié n’est actuellement sous le seuil.
              </p>
            ) : (
              <div className="mt-4 grid gap-3 md:grid-cols-2">
                {(overview.stock.lowStockItems ?? []).map((product) => (
                  <a
                    key={product.id}
                    className="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-brand-300 hover:bg-brand-50"
                    href={`/admin/catalog/products/${product.id}/edit`}
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <div className="font-semibold text-slate-950">{product.name}</div>
                        <div className="mt-1 text-xs text-slate-500">{product.sku} · {product.category}</div>
                      </div>
                      <span className={`rounded-full px-3 py-1 text-xs font-semibold ${product.stock === 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}`}>
                        Stock : {product.stock} / seuil {product.lowStockThreshold ?? overview.stock.lowStockThreshold ?? 3}
                      </span>
                    </div>
                  </a>
                ))}
              </div>
            )}
          </div>
        </section>
      )}

      <section className="mb-8 grid gap-6 xl:grid-cols-2">
        <ActionCard
          title="Préparer et expédier"
          description="File des commandes à traiter. Renseigne le suivi puis marque la commande comme expédiée."
        >
          <div className="space-y-3">
            {fulfillmentOrders.length === 0 ? (
              <p className="text-sm text-slate-500">Aucune commande à préparer.</p>
            ) : fulfillmentOrders.map((order) => {
              const form = shippingForms[order.id] ?? { carrier: order.delivery.carrier ?? '', trackingNumber: order.delivery.trackingNumber ?? '', trackingUrl: order.delivery.trackingUrl ?? '' };
              return (
                <div key={order.id} className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                      <div className="font-semibold text-slate-950">{order.number} · {formatPrice(order.totalPriceCents)}</div>
                      <div className="mt-1 text-xs text-slate-500">{order.customer.name} · {order.shipping.postalCode} {order.shipping.city}</div>
                      <div className="mt-2 text-xs text-slate-600">{order.items.map((item) => `${item.quantity}× ${item.name}`).join(' · ')}</div>
                    </div>
                    <button className={secondaryActionClass} type="button" onClick={() => navigate(`/admin/orders/${order.id}`)}>Voir</button>
                  </div>
                  <div className="mt-3 grid gap-2 sm:grid-cols-3">
                    <input className={inputClass} placeholder="Transporteur" value={form.carrier} onChange={(e) => setShippingForms((p) => ({ ...p, [order.id]: { ...form, carrier: e.target.value } }))} />
                    <input className={inputClass} placeholder="Numéro de suivi" value={form.trackingNumber} onChange={(e) => setShippingForms((p) => ({ ...p, [order.id]: { ...form, trackingNumber: e.target.value } }))} />
                    <input className={inputClass} placeholder="Lien de suivi" value={form.trackingUrl} onChange={(e) => setShippingForms((p) => ({ ...p, [order.id]: { ...form, trackingUrl: e.target.value } }))} />
                  </div>
                  <button className={`${primaryActionClass} mt-3`} type="button" onClick={() => submitShipOrder(order.id)}>Marquer expédiée</button>
                </div>
              );
            })}
          </div>
        </ActionCard>

        <ActionCard
          title="Créer un dossier SAV"
          description="À utiliser quand un client signale un problème ou quand une commande nécessite un suivi manuel."
        >
          <div className="grid gap-3 sm:grid-cols-2">
            <Field label="ID client" helper="Obligatoire. À récupérer depuis la fiche client.">
              <input className={inputClass} inputMode="numeric" placeholder="Ex. 42" value={supportForm.customerId} onChange={(e) => setSupportForm((p) => ({ ...p, customerId: e.target.value }))} />
            </Field>
            <Field label="ID commande" helper="Optionnel si la demande n’est pas liée à une commande.">
              <input className={inputClass} inputMode="numeric" placeholder="Ex. 128" value={supportForm.orderId} onChange={(e) => setSupportForm((p) => ({ ...p, orderId: e.target.value }))} />
            </Field>
            <Field label="Sujet" className="sm:col-span-2">
              <input className={inputClass} placeholder="Ex. Produit reçu endommagé" value={supportForm.subject} onChange={(e) => setSupportForm((p) => ({ ...p, subject: e.target.value }))} />
            </Field>
            <Field label="Type de demande" className="sm:col-span-2">
              <select className={inputClass} value={supportForm.reason} onChange={(e) => setSupportForm((p) => ({ ...p, reason: e.target.value }))}>
                <option value="defective_product">Produit défectueux</option>
                <option value="wrong_order">Erreur commande</option>
                <option value="return">Retour</option>
                <option value="exchange">Échange</option>
                <option value="refund">Remboursement</option>
                <option value="other">Autre</option>
              </select>
            </Field>
            <Field label="Message / contexte" className="sm:col-span-2">
              <textarea className={inputClass} rows={3} placeholder="Résumé clair du problème client" value={supportForm.message} onChange={(e) => setSupportForm((p) => ({ ...p, message: e.target.value }))} />
            </Field>
            <Field label="Notes internes" className="sm:col-span-2" helper="Visible uniquement côté admin.">
              <textarea className={inputClass} rows={3} placeholder="Décision, historique, prochaine action..." value={supportForm.internalNotes} onChange={(e) => setSupportForm((p) => ({ ...p, internalNotes: e.target.value }))} />
            </Field>
          </div>
          <button className={primaryActionClass} type="button" onClick={submitSupport} disabled={!supportForm.customerId}>
            Créer le dossier SAV
          </button>
        </ActionCard>

        <ActionCard
          title="Créer un suivi de remboursement"
          description="Ce suivi sert à piloter la décision. Il ne déclenche pas automatiquement un remboursement Stripe."
          warning="Action comptable sensible : vérifie la commande et le montant avant de marquer le suivi comme traité."
        >
          <div className="grid gap-3 sm:grid-cols-2">
            <Field label="ID commande">
              <input className={inputClass} inputMode="numeric" placeholder="Ex. 128" value={refundForm.orderId} onChange={(e) => setRefundForm((p) => ({ ...p, orderId: e.target.value }))} />
            </Field>
            <Field label="Montant" helper="En centimes. Exemple : 1990 = 19,90 €.">
              <input className={inputClass} inputMode="numeric" placeholder="Ex. 1990" value={refundForm.amountCents} onChange={(e) => setRefundForm((p) => ({ ...p, amountCents: e.target.value }))} />
            </Field>
            <Field label="Motif" className="sm:col-span-2">
              <input className={inputClass} placeholder="Ex. Retour client accepté" value={refundForm.reason} onChange={(e) => setRefundForm((p) => ({ ...p, reason: e.target.value }))} />
            </Field>
            <Field label="Notes internes" className="sm:col-span-2">
              <textarea className={inputClass} rows={3} placeholder="Décision, preuve, référence Stripe si déjà traitée..." value={refundForm.internalNotes} onChange={(e) => setRefundForm((p) => ({ ...p, internalNotes: e.target.value }))} />
            </Field>
          </div>
          <button className={primaryActionClass} type="button" onClick={submitRefund} disabled={!refundForm.orderId || !refundForm.amountCents}>
            Créer le suivi remboursement
          </button>
        </ActionCard>

        <ActionCard
          title="Corriger un stock"
          description="Ajoute ou retire une quantité avec une trace exploitable dans l’historique."
        >
          <div className="grid gap-3 sm:grid-cols-2">
            <Field label="ID produit">
              <input className={inputClass} inputMode="numeric" placeholder="Ex. 15" value={stockForm.productId} onChange={(e) => setStockForm((p) => ({ ...p, productId: e.target.value }))} />
            </Field>
            <Field label="Quantité à appliquer" helper="+5 ajoute du stock, -2 retire du stock.">
              <input className={inputClass} placeholder="Ex. +5 ou -2" value={stockForm.delta} onChange={(e) => setStockForm((p) => ({ ...p, delta: e.target.value }))} />
            </Field>
            <Field label="Motif" className="sm:col-span-2">
              <select className={inputClass} value={stockForm.reason} onChange={(e) => setStockForm((p) => ({ ...p, reason: e.target.value }))}>
                <option value="adjustment">Correction</option>
                <option value="restock">Réapprovisionnement</option>
                <option value="return">Retour</option>
                <option value="damage">Casse</option>
                <option value="reservation">Réservation</option>
              </select>
            </Field>
            <Field label="Note" className="sm:col-span-2">
              <textarea className={inputClass} rows={3} placeholder="Pourquoi ce stock change ?" value={stockForm.note} onChange={(e) => setStockForm((p) => ({ ...p, note: e.target.value }))} />
            </Field>
          </div>
          <button className={primaryActionClass} type="button" onClick={submitStock} disabled={!stockForm.productId || !stockForm.delta}>
            Enregistrer le mouvement
          </button>
        </ActionCard>

        <ActionCard
          title="Actions rapides"
          description="Regroupe les actions ponctuelles qui modifient plusieurs données ou transforment un devis."
        >
          <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <h3 className="font-semibold text-slate-900">Changer le statut de commandes</h3>
            <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_180px]">
              <Field label="IDs commandes" helper="Sépare les IDs par des virgules. Exemple : 101, 102, 103.">
                <input className={inputClass} placeholder="101, 102, 103" value={bulkForm.orderIds} onChange={(e) => setBulkForm((p) => ({ ...p, orderIds: e.target.value }))} />
              </Field>
              <Field label="Nouveau statut">
                <select className={inputClass} value={bulkForm.status} onChange={(e) => setBulkForm((p) => ({ ...p, status: e.target.value }))}>
                  <option value="confirmed">Confirmée</option>
                  <option value="delivered">Livrée</option>
                  <option value="cancelled">Annulée</option>
                  <option value="pending">En attente</option>
                </select>
              </Field>
            </div>
            <button className={`${primaryActionClass} mt-3`} type="button" onClick={submitBulk} disabled={!bulkForm.orderIds}>
              Appliquer le statut
            </button>
          </div>

          <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <h3 className="font-semibold text-slate-900">Convertir un devis en commande</h3>
            <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
              <Field label="ID ou numéro du devis" helper="Tu peux saisir l’ID interne ou le numéro visible dans la liste des devis.">
                <input className={inputClass} placeholder="Ex. 77 ou DEV-2026-001" value={quoteReference} onChange={(e) => setQuoteReference(e.target.value)} />
              </Field>
              <button className={primaryActionClass} type="button" onClick={submitQuoteConversion} disabled={!quoteReference.trim() || quoteConversionStatus === 'loading'}>
                {quoteConversionStatus === 'loading' ? 'Conversion...' : 'Convertir'}
              </button>
            </div>
            {quoteConversionMessage && (
              <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                {quoteConversionMessage}
              </p>
            )}
          </div>
        </ActionCard>
      </section>

      <section className="mb-8">
        <div className="mb-3">
          <h2 className="text-lg font-semibold text-slate-950">Suivi récent</h2>
          <p className="text-sm text-slate-500">Les dernières demandes et opérations à contrôler.</p>
        </div>
        <div className="grid gap-6 xl:grid-cols-2">
        <List title="Demandes SAV" items={support.map((item) => ({
          key: item.id,
          title: `#${item.id} · ${item.subject}`,
          meta: `${item.customer.name} · ${item.statusLabel} · ${formatDate(item.updatedAt)}`,
          action: (
            <div className="space-y-2">
              <select className={inputClass} value={item.status} onChange={(e) => void updateSupportRequest(item.id, { status: e.target.value }).then(refresh)}>
                <option value="new">Nouveau</option>
                <option value="in_progress">En cours</option>
                <option value="waiting_customer">En attente client</option>
                <option value="resolved">Résolu</option>
                <option value="refused">Refusé</option>
              </select>
              <input
                className={inputClass}
                placeholder="Sujet réponse client"
                value={supportReplies[item.id]?.subject ?? `Réponse SAV #${item.id}`}
                onChange={(e) => setSupportReplies((p) => ({ ...p, [item.id]: { subject: e.target.value, message: p[item.id]?.message ?? '' } }))}
              />
              <textarea
                className={inputClass}
                rows={2}
                placeholder="Message à envoyer au client"
                value={supportReplies[item.id]?.message ?? ''}
                onChange={(e) => setSupportReplies((p) => ({ ...p, [item.id]: { subject: p[item.id]?.subject ?? `Réponse SAV #${item.id}`, message: e.target.value } }))}
              />
              <button className={secondaryActionClass} type="button" onClick={() => submitSupportReply(item.id)} disabled={!supportReplies[item.id]?.message}>
                Répondre au client
              </button>
            </div>
          ),
        }))} />

        <List title="Remboursements" items={refunds.map((item) => ({
          key: item.id,
          title: `#${item.id} · ${item.order.number} · ${formatPrice(item.amountCents)}`,
          meta: `${item.status} · ${item.reason || 'Sans motif'} · ${formatDate(item.updatedAt)}`,
          action: (
            <div className="space-y-2">
              <select className={inputClass} value={item.status} onChange={(e) => void updateRefund(item.id, { status: e.target.value }).then(refresh)}>
                <option value="requested">Demandé</option>
                <option value="approved">Approuvé</option>
                <option value="rejected">Refusé</option>
                <option value="processed">Traité</option>
              </select>
              <input
                className={inputClass}
                placeholder="Tape REMBOURSER pour déclencher Stripe"
                value={refundConfirmations[item.id] ?? ''}
                onChange={(e) => setRefundConfirmations((p) => ({ ...p, [item.id]: e.target.value }))}
                disabled={Boolean(item.stripeRefundId) || item.status === 'processed'}
              />
              <button
                className={secondaryActionClass}
                type="button"
                onClick={() => submitStripeRefund(item.id)}
                disabled={(refundConfirmations[item.id] ?? '') !== 'REMBOURSER' || Boolean(item.stripeRefundId) || item.status === 'processed'}
              >
                Déclencher remboursement Stripe
              </button>
              {item.stripeRefundId && <p className="text-xs text-emerald-700">Stripe : {item.stripeRefundId}</p>}
            </div>
          ),
        }))} />

        <List title="Mouvements de stock" items={stock.map((item) => ({
          key: item.id,
          title: `${item.product.sku} · ${item.product.name}`,
          meta: `${item.delta > 0 ? '+' : ''}${item.delta} · ${item.stockBefore} → ${item.stockAfter} · ${formatDate(item.createdAt)}`,
          action: (
            <div className="flex flex-col gap-2 sm:flex-row">
              <input
                className={inputClass}
                inputMode="numeric"
                placeholder="Nouveau seuil stock faible"
                value={stockThresholds[item.product.id] ?? ''}
                onChange={(e) => setStockThresholds((p) => ({ ...p, [item.product.id]: e.target.value }))}
              />
              <button className={secondaryActionClass} type="button" onClick={() => submitStockThreshold(item.product.id)} disabled={!stockThresholds[item.product.id]}>
                Modifier seuil
              </button>
            </div>
          ),
        }))} />

        <List title="Emails transactionnels" items={emails.map((item, index) => ({
          key: `${item.createdAt}-${index}`,
          title: `${item.statusLabel ?? (item.status === 'failed' ? 'Échec' : 'Envoyé')} · ${item.scenarioLabel ?? item.scenario}`,
          meta: `${item.recipient || 'Destinataire inconnu'} · ${item.related?.label || item.subject || ''} · ${formatDate(item.createdAt)}`,
        }))} />
        </div>
      </section>

      <section className={cardClass}>
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-lg font-semibold text-slate-950">Exports CSV</h2>
            <p className="text-sm text-slate-500">Télécharge les données pour contrôle, comptabilité ou reporting.</p>
          </div>
        </div>
        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {['orders', 'customers', 'products', 'quotes', 'refunds', 'support'].map((resource) => (
            <a key={resource} className={secondaryActionClass + ' text-center'} href={`/api/admin/operations/exports/${resource}.csv`}>
              Export {exportLabels[resource]}
            </a>
          ))}
        </div>
      </section>
    </PageContainer>
  );
};

const StatCard = ({ label, value, helper, tone = 'neutral' }: {
  label: string;
  value: number;
  helper: string;
  tone?: 'neutral' | 'warning' | 'danger';
}) => {
  const toneClass = {
    neutral: 'border-slate-200 bg-white text-slate-950',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    danger: 'border-red-200 bg-red-50 text-red-950',
  }[tone];

  return (
    <div className={`rounded-2xl border p-5 shadow-sm ${toneClass}`}>
      <div className="text-sm font-medium opacity-75">{label}</div>
      <div className="mt-2 text-3xl font-semibold">{value}</div>
      <p className="mt-2 text-xs leading-5 opacity-75">{helper}</p>
    </div>
  );
};

const ActionCard = ({ title, description, warning, children }: {
  title: string;
  description: string;
  warning?: string;
  children: ReactNode;
}) => (
  <div className={cardClass}>
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-slate-950">{title}</h2>
      <p className="mt-1 text-sm leading-6 text-slate-600">{description}</p>
      {warning && (
        <p className="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-900">
          {warning}
        </p>
      )}
    </div>
    <div className="space-y-4">{children}</div>
  </div>
);

const Field = ({ label, helper, className = '', children }: {
  label: string;
  helper?: string;
  className?: string;
  children: ReactNode;
}) => (
  <label className={`block ${className}`}>
    <span className="text-sm font-medium text-slate-700">{label}</span>
    <span className="mt-1 block">{children}</span>
    {helper && <span className="mt-1 block text-xs leading-5 text-slate-500">{helper}</span>}
  </label>
);

const List = ({ title, items }: {
  title: string;
  items: Array<{ key: string | number; title: string; meta: string; action?: ReactNode }>;
}) => (
  <div className={cardClass}>
    <h2 className="text-lg font-semibold">{title}</h2>
    <div className="mt-4 space-y-3">
      {items.length === 0 ? (
        <p className="text-sm text-slate-500">Aucun élément.</p>
      ) : items.map((item) => (
        <div key={item.key} className="rounded-xl border border-slate-100 bg-slate-50 p-3">
          <div className="font-medium text-slate-900">{item.title}</div>
          <div className="mt-1 text-sm text-slate-500">{item.meta}</div>
          {item.action && <div className="mt-3">{item.action}</div>}
        </div>
      ))}
    </div>
  </div>
);
