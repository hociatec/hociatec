import { useEffect, useMemo, useState, type ReactNode } from 'react';

import { PageContainer } from '@/shared/components/PageContainer';
import {
  bulkUpdateOrderStatus,
  convertQuoteToOrder,
  createRefund,
  createStockMovement,
  createSupportRequest,
  fetchEmailLogs,
  fetchOperationsOverview,
  fetchRefunds,
  fetchStockMovements,
  fetchSupportRequests,
  updateRefund,
  updateSupportRequest,
  type EmailLogDto,
  type OperationsOverviewDto,
  type RefundRequestDto,
  type StockMovementDto,
  type SupportRequestDto,
} from '@/features/admin/operations/api';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100);

const formatDate = (value: string) => new Date(value).toLocaleString('fr-FR');

const cardClass = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm';
const inputClass = 'rounded-xl border border-slate-200 px-3 py-2 text-sm';

export const AdminOperationsPage = () => {
  const [overview, setOverview] = useState<OperationsOverviewDto | null>(null);
  const [support, setSupport] = useState<SupportRequestDto[]>([]);
  const [refunds, setRefunds] = useState<RefundRequestDto[]>([]);
  const [stock, setStock] = useState<StockMovementDto[]>([]);
  const [emails, setEmails] = useState<EmailLogDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [message, setMessage] = useState<string | null>(null);

  const [supportForm, setSupportForm] = useState({ customerId: '', orderId: '', subject: '', reason: 'other', message: '', internalNotes: '' });
  const [refundForm, setRefundForm] = useState({ orderId: '', amountCents: '', reason: '', internalNotes: '' });
  const [stockForm, setStockForm] = useState({ productId: '', delta: '', reason: 'adjustment', note: '' });
  const [bulkForm, setBulkForm] = useState({ orderIds: '', status: 'confirmed' });
  const [quoteId, setQuoteId] = useState('');

  const refresh = () => {
    setStatus('loading');
    setMessage(null);
    Promise.all([
      fetchOperationsOverview(),
      fetchSupportRequests(),
      fetchRefunds(),
      fetchStockMovements(),
      fetchEmailLogs(),
    ])
      .then(([overviewData, supportData, refundData, stockData, emailData]) => {
        setOverview(overviewData);
        setSupport(supportData);
        setRefunds(refundData);
        setStock(stockData);
        setEmails(emailData);
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
    void convertQuoteToOrder(Number(quoteId))
      .then((order) => {
        setMessage(`Devis converti en commande ${order.number}.`);
        setQuoteId('');
      })
      .catch((error: unknown) => setMessage(error instanceof Error ? error.message : 'Erreur conversion devis.'));
  };

  return (
    <PageContainer title="Exploitation admin">
      <div className="mb-6 space-y-2 text-sm text-slate-600">
        <p>SAV, remboursements, stock, exports, emails, actions groupées et conversion devis → commande.</p>
        {message && <div className="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-brand-900">{message}</div>}
        {status === 'loading' && <p>Chargement...</p>}
      </div>

      {overview && (
        <section className="mb-8 grid gap-4 md:grid-cols-4">
          <div className={cardClass}>
            <div className="text-sm text-slate-500">SAV ouverts</div>
            <div className="mt-2 text-3xl font-semibold">{overview.support.openCount}</div>
          </div>
          <div className={cardClass}>
            <div className="text-sm text-slate-500">Remboursements à traiter</div>
            <div className="mt-2 text-3xl font-semibold">{overview.refunds.pendingCount}</div>
          </div>
          <div className={cardClass}>
            <div className="text-sm text-slate-500">Stocks faibles</div>
            <div className="mt-2 text-3xl font-semibold">{overview.stock.lowStockCount}</div>
          </div>
          <div className={cardClass}>
            <div className="text-sm text-slate-500">Emails échoués</div>
            <div className="mt-2 text-3xl font-semibold">{failedEmails}</div>
          </div>
        </section>
      )}

      <section className="grid gap-6 xl:grid-cols-2">
        <div className={cardClass}>
          <h2 className="text-lg font-semibold">Créer une demande SAV</h2>
          <div className="mt-4 grid gap-3">
            <input className={inputClass} placeholder="ID client" value={supportForm.customerId} onChange={(e) => setSupportForm((p) => ({ ...p, customerId: e.target.value }))} />
            <input className={inputClass} placeholder="ID commande (optionnel)" value={supportForm.orderId} onChange={(e) => setSupportForm((p) => ({ ...p, orderId: e.target.value }))} />
            <input className={inputClass} placeholder="Sujet" value={supportForm.subject} onChange={(e) => setSupportForm((p) => ({ ...p, subject: e.target.value }))} />
            <select className={inputClass} value={supportForm.reason} onChange={(e) => setSupportForm((p) => ({ ...p, reason: e.target.value }))}>
              <option value="defective_product">Produit défectueux</option>
              <option value="wrong_order">Erreur commande</option>
              <option value="return">Retour</option>
              <option value="exchange">Échange</option>
              <option value="refund">Remboursement</option>
              <option value="other">Autre</option>
            </select>
            <textarea className={inputClass} placeholder="Message client / contexte" value={supportForm.message} onChange={(e) => setSupportForm((p) => ({ ...p, message: e.target.value }))} />
            <textarea className={inputClass} placeholder="Notes internes" value={supportForm.internalNotes} onChange={(e) => setSupportForm((p) => ({ ...p, internalNotes: e.target.value }))} />
            <button className="catalog-admin-actions__edit" type="button" onClick={submitSupport}>Créer le SAV</button>
          </div>
        </div>

        <div className={cardClass}>
          <h2 className="text-lg font-semibold">Remboursement à suivre</h2>
          <div className="mt-4 grid gap-3">
            <input className={inputClass} placeholder="ID commande" value={refundForm.orderId} onChange={(e) => setRefundForm((p) => ({ ...p, orderId: e.target.value }))} />
            <input className={inputClass} placeholder="Montant en centimes" value={refundForm.amountCents} onChange={(e) => setRefundForm((p) => ({ ...p, amountCents: e.target.value }))} />
            <input className={inputClass} placeholder="Motif" value={refundForm.reason} onChange={(e) => setRefundForm((p) => ({ ...p, reason: e.target.value }))} />
            <textarea className={inputClass} placeholder="Notes internes" value={refundForm.internalNotes} onChange={(e) => setRefundForm((p) => ({ ...p, internalNotes: e.target.value }))} />
            <button className="catalog-admin-actions__edit" type="button" onClick={submitRefund}>Créer le suivi</button>
            <p className="text-xs text-slate-500">Cette action ne déclenche pas un remboursement Stripe automatiquement.</p>
          </div>
        </div>

        <div className={cardClass}>
          <h2 className="text-lg font-semibold">Mouvement de stock</h2>
          <div className="mt-4 grid gap-3">
            <input className={inputClass} placeholder="ID produit" value={stockForm.productId} onChange={(e) => setStockForm((p) => ({ ...p, productId: e.target.value }))} />
            <input className={inputClass} placeholder="Delta (+5 ou -2)" value={stockForm.delta} onChange={(e) => setStockForm((p) => ({ ...p, delta: e.target.value }))} />
            <select className={inputClass} value={stockForm.reason} onChange={(e) => setStockForm((p) => ({ ...p, reason: e.target.value }))}>
              <option value="adjustment">Correction</option>
              <option value="restock">Réapprovisionnement</option>
              <option value="return">Retour</option>
              <option value="damage">Casse</option>
              <option value="reservation">Réservation</option>
            </select>
            <textarea className={inputClass} placeholder="Note" value={stockForm.note} onChange={(e) => setStockForm((p) => ({ ...p, note: e.target.value }))} />
            <button className="catalog-admin-actions__edit" type="button" onClick={submitStock}>Ajuster le stock</button>
          </div>
        </div>

        <div className={cardClass}>
          <h2 className="text-lg font-semibold">Actions commerciales</h2>
          <div className="mt-4 grid gap-3">
            <input className={inputClass} placeholder="IDs commandes séparés par virgule" value={bulkForm.orderIds} onChange={(e) => setBulkForm((p) => ({ ...p, orderIds: e.target.value }))} />
            <select className={inputClass} value={bulkForm.status} onChange={(e) => setBulkForm((p) => ({ ...p, status: e.target.value }))}>
              <option value="confirmed">Confirmer</option>
              <option value="delivered">Livrer</option>
              <option value="cancelled">Annuler</option>
              <option value="pending">Remettre en attente</option>
            </select>
            <button className="catalog-admin-actions__edit" type="button" onClick={submitBulk}>Appliquer aux commandes</button>
            <input className={inputClass} placeholder="ID devis à convertir" value={quoteId} onChange={(e) => setQuoteId(e.target.value)} />
            <button className="catalog-admin-actions__edit" type="button" onClick={submitQuoteConversion}>Convertir devis → commande</button>
          </div>
        </div>
      </section>

      <section className="mt-8 grid gap-6 xl:grid-cols-2">
        <List title="Demandes SAV" items={support.map((item) => ({
          key: item.id,
          title: `#${item.id} · ${item.subject}`,
          meta: `${item.customer.name} · ${item.statusLabel} · ${formatDate(item.updatedAt)}`,
          action: (
            <select className={inputClass} value={item.status} onChange={(e) => void updateSupportRequest(item.id, { status: e.target.value }).then(refresh)}>
              <option value="new">Nouveau</option>
              <option value="in_progress">En cours</option>
              <option value="waiting_customer">En attente client</option>
              <option value="resolved">Résolu</option>
              <option value="refused">Refusé</option>
            </select>
          ),
        }))} />

        <List title="Remboursements" items={refunds.map((item) => ({
          key: item.id,
          title: `#${item.id} · ${item.order.number} · ${formatPrice(item.amountCents)}`,
          meta: `${item.status} · ${item.reason || 'Sans motif'} · ${formatDate(item.updatedAt)}`,
          action: (
            <select className={inputClass} value={item.status} onChange={(e) => void updateRefund(item.id, { status: e.target.value }).then(refresh)}>
              <option value="requested">Demandé</option>
              <option value="approved">Approuvé</option>
              <option value="rejected">Refusé</option>
              <option value="processed">Traité</option>
            </select>
          ),
        }))} />

        <List title="Mouvements de stock" items={stock.map((item) => ({
          key: item.id,
          title: `${item.product.sku} · ${item.product.name}`,
          meta: `${item.delta > 0 ? '+' : ''}${item.delta} · ${item.stockBefore} → ${item.stockAfter} · ${formatDate(item.createdAt)}`,
        }))} />

        <List title="Emails transactionnels" items={emails.map((item, index) => ({
          key: `${item.createdAt}-${index}`,
          title: `${item.status === 'failed' ? 'Échec' : 'Envoyé'} · ${item.scenario}`,
          meta: `${item.recipient || 'Destinataire inconnu'} · ${item.related?.label || item.subject || ''} · ${formatDate(item.createdAt)}`,
        }))} />
      </section>

      <section className={cardClass + ' mt-8'}>
        <h2 className="text-lg font-semibold">Exports</h2>
        <div className="mt-4 flex flex-wrap gap-3">
          {['orders', 'customers', 'products', 'quotes', 'refunds', 'support'].map((resource) => (
            <a key={resource} className="catalog-admin-actions__edit" href={`/api/admin/operations/exports/${resource}.csv`}>
              Export {resource}
            </a>
          ))}
        </div>
      </section>
    </PageContainer>
  );
};

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
