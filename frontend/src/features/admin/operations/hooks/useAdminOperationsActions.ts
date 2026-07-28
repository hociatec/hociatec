import { useCallback, useState } from 'react';
import { useNavigate } from 'react-router';

import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import {
  bulkUpdateOrderStatus, convertQuoteToOrder, createRefund, createStockMovement,
  createSupportRequest, processStripeRefund, replySupportRequest, shipFulfillmentOrder,
  updateLowStockThreshold, updateRefund, updateSupportRequest,
} from '@/features/admin/operations/api';
import type { BulkForm, RefundForm, ShippingForms, StockForm, SupportForm, SupportReplies } from '@/features/admin/operations/components/operationsTypes';

const emptySupportForm: SupportForm = { customerId: '', orderId: '', subject: '', reason: 'other', message: '', internalNotes: '' };
const emptyRefundForm: RefundForm = { orderId: '', amountCents: '', reason: '', internalNotes: '' };
const emptyStockForm: StockForm = { productId: '', delta: '', reason: 'adjustment', note: '' };
const emptyBulkForm: BulkForm = { orderIds: '', status: 'confirmed' };
const emptyShippingForm = { carrier: '', trackingNumber: '', trackingUrl: '' };

export const useAdminOperationsActions = (refresh: () => Promise<void>) => {
  const navigate = useNavigate();
  const [actionMessage, setActionMessage] = useState<string | null>(null);
  const refreshOperations = useCallback(async () => { setActionMessage(null); await refresh(); }, [refresh]);
  const [supportForm, setSupportForm] = useState<SupportForm>(emptySupportForm);
  const [refundForm, setRefundForm] = useState<RefundForm>(emptyRefundForm);
  const [stockForm, setStockForm] = useState<StockForm>(emptyStockForm);
  const [bulkForm, setBulkForm] = useState<BulkForm>(emptyBulkForm);
  const [quoteReference, setQuoteReference] = useState('');
  const [quoteConversionStatus, setQuoteConversionStatus] = useState<'idle' | 'loading' | 'error'>('idle');
  const [quoteConversionMessage, setQuoteConversionMessage] = useState<string | null>(null);
  const [shippingForms, setShippingForms] = useState<ShippingForms>({});
  const [supportReplies, setSupportReplies] = useState<SupportReplies>({});
  const [refundConfirmations, setRefundConfirmations] = useState<Record<number, string>>({});
  const [stockThresholds, setStockThresholds] = useState<Record<number, string>>({});

  const runAction = useCallback(async (action: () => Promise<void>, successMessage: string, fallback: string) => {
    try { await action(); await refreshOperations(); setActionMessage(successMessage); }
    catch (error) { setActionMessage(getHttpErrorMessage(error, fallback)); }
  }, [refreshOperations]);
  const submitSupport = useCallback(() => void runAction(async () => { await createSupportRequest({ customerId: Number(supportForm.customerId), orderId: supportForm.orderId ? Number(supportForm.orderId) : null, subject: supportForm.subject || 'Demande SAV', reason: supportForm.reason, message: supportForm.message, internalNotes: supportForm.internalNotes }); setSupportForm(emptySupportForm); }, 'Demande SAV créée.', 'Erreur SAV.'), [runAction, supportForm]);
  const submitRefund = useCallback(() => void runAction(async () => { await createRefund({ orderId: Number(refundForm.orderId), amountCents: Number(refundForm.amountCents), reason: refundForm.reason, internalNotes: refundForm.internalNotes }); setRefundForm(emptyRefundForm); }, 'Suivi de remboursement créé.', 'Erreur remboursement.'), [refundForm, runAction]);
  const submitStock = useCallback(() => void runAction(async () => { await createStockMovement({ productId: Number(stockForm.productId), delta: Number(stockForm.delta), reason: stockForm.reason, note: stockForm.note }); setStockForm(emptyStockForm); }, 'Stock ajusté.', 'Erreur stock.'), [runAction, stockForm]);
  const submitBulk = useCallback(() => {
    const ids = bulkForm.orderIds.split(',').map((value) => Number(value.trim())).filter((value) => Number.isFinite(value) && value > 0);
    void runAction(async () => { await bulkUpdateOrderStatus(ids, bulkForm.status); setBulkForm(emptyBulkForm); }, `${ids.length} commande(s) mise(s) à jour.`, 'Erreur action groupée.');
  }, [bulkForm, runAction]);
  const submitQuoteConversion = useCallback(() => {
    const reference = quoteReference.trim();
    if (!reference) { setQuoteConversionStatus('error'); setQuoteConversionMessage('Renseigne l’ID ou le numéro du devis.'); return; }
    setQuoteConversionStatus('loading'); setQuoteConversionMessage(null);
    void convertQuoteToOrder(reference).then((order) => { setActionMessage(`Devis converti en commande ${order.number}.`); setQuoteReference(''); navigate(`/admin/orders/${order.id}`); }).catch((error) => setQuoteConversionMessage(getHttpErrorMessage(error, 'Erreur conversion devis.'))).finally(() => setQuoteConversionStatus('idle'));
  }, [navigate, quoteReference]);
  const submitShipOrder = useCallback((orderId: number) => { const payload = shippingForms[orderId] ?? emptyShippingForm; void runAction(async () => { await shipFulfillmentOrder(orderId, payload); setShippingForms((current) => ({ ...current, [orderId]: emptyShippingForm })); }, 'Commande marquée comme expédiée.', 'Erreur expédition.'); }, [runAction, shippingForms]);
  const submitSupportReply = useCallback((supportId: number) => { const payload = supportReplies[supportId] ?? { subject: `Réponse SAV #${supportId}`, message: '' }; void runAction(async () => { await replySupportRequest(supportId, { ...payload, status: 'waiting_customer' }); setSupportReplies((current) => ({ ...current, [supportId]: { subject: '', message: '' } })); }, 'Réponse SAV envoyée au client.', 'Erreur réponse SAV.'); }, [runAction, supportReplies]);
  const submitStripeRefund = useCallback((refundId: number) => { void runAction(async () => { const refund = await processStripeRefund(refundId, { confirmation: refundConfirmations[refundId] ?? '' }); setActionMessage(`Remboursement Stripe traité : ${refund.stripeRefundId ?? 'référence Stripe créée'}.`); setRefundConfirmations((current) => ({ ...current, [refundId]: '' })); }, 'Remboursement Stripe traité.', 'Erreur remboursement Stripe.'); }, [refundConfirmations, runAction]);
  const submitStockThreshold = useCallback((productId: number) => { void runAction(() => updateLowStockThreshold(productId, Number(stockThresholds[productId])).then(() => undefined), 'Seuil de stock faible mis à jour.', 'Erreur seuil stock.'); }, [runAction, stockThresholds]);
  const updateRefundStatus = useCallback((refundId: number, nextStatus: string) => { void runAction(() => updateRefund(refundId, { status: nextStatus }).then(() => undefined), 'Statut remboursement mis à jour.', 'Erreur statut remboursement.'); }, [runAction]);
  const updateSupportStatus = useCallback((supportId: number, nextStatus: string) => { void runAction(() => updateSupportRequest(supportId, { status: nextStatus }).then(() => undefined), 'Statut SAV mis à jour.', 'Erreur statut SAV.'); }, [runAction]);

  return { message: actionMessage, refresh: refreshOperations, supportForm, refundForm, stockForm, bulkForm, quoteReference, quoteConversionStatus, quoteConversionMessage, shippingForms, supportReplies, refundConfirmations, stockThresholds, setSupportForm, setRefundForm, setStockForm, setBulkForm, setQuoteReference, setShippingForms, setSupportReplies, setRefundConfirmations, setStockThresholds, submitSupport, submitRefund, submitStock, submitBulk, submitQuoteConversion, submitShipOrder, submitSupportReply, submitStripeRefund, submitStockThreshold, updateRefundStatus, updateSupportStatus };
};
