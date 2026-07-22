import { useEffect, useMemo, useState } from 'react';
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
import {
  OperationsActionsSection,
  OperationsRecentSection,
} from '@/features/admin/extracted/operations/AdminOperationsSections';
import {
  OperationsExports,
  OperationsHeader,
  OperationsPriorities,
} from '@/features/admin/extracted/operations/AdminOperationsWidgets';

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
    <PageContainer size="admin" title="Centre exploitation">
      <OperationsHeader message={message} onRefresh={refresh} status={status} />
      {overview && <OperationsPriorities failedEmails={failedEmails} hasPriorities={hasPriorities} overview={overview} />}
      <OperationsActionsSection
        {...{ bulkForm, fulfillmentOrders, quoteConversionMessage, quoteConversionStatus, quoteReference, refundForm, setBulkForm, setQuoteReference, setRefundForm, setShippingForms, setStockForm, setSupportForm, shippingForms, stockForm, submitBulk, submitQuoteConversion, submitRefund, submitShipOrder, submitStock, submitSupport, supportForm }}
      />
      <OperationsRecentSection
        {...{ emails, refundConfirmations, refunds, setRefundConfirmations, setStockThresholds, setSupportReplies, stock, stockThresholds, submitStockThreshold, submitStripeRefund, submitSupportReply, support, supportReplies }}
        updateRefundStatus={(refundId, nextStatus) => void updateRefund(refundId, { status: nextStatus }).then(refresh)}
        updateSupportStatus={(supportId, nextStatus) => void updateSupportRequest(supportId, { status: nextStatus }).then(refresh)}
      />
      <OperationsExports exportLabels={exportLabels} />
    </PageContainer>
  );
};
