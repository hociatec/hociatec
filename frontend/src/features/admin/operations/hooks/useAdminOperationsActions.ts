import { useCallback, useState } from 'react';
import { useNavigate } from 'react-router';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import {
  bulkUpdateOrderStatus, convertQuoteToOrder, createRefund, createStockMovement,
  createSupportRequest, processStripeRefund, replySupportRequest, shipFulfillmentOrder,
  updateLowStockThreshold, updateRefund, updateSupportRequest,
} from '@/features/admin/operations/api';
import type { BulkForm, RefundForm, ShippingForms, StockForm, SupportForm, SupportReplies } from '@/features/admin/operations/components/operationsTypes';
import { adminOperationsQueryKeys } from '@/features/admin/operations/queryKeys';
import { parseNullableInteger, parseNullablePositiveInteger } from '@/shared/lib/parsers';

const emptySupportForm: SupportForm = { customerId: '', orderId: '', subject: '', reason: 'other', message: '', internalNotes: '' };
const emptyRefundForm: RefundForm = { orderId: '', amountCents: '', reason: '', internalNotes: '' };
const emptyStockForm: StockForm = { productId: '', delta: '', reason: 'adjustment', note: '' };
const emptyBulkForm: BulkForm = { orderIds: '', status: 'confirmed' };
const emptyShippingForm = { carrier: '', trackingNumber: '', trackingUrl: '' };

export const useAdminOperationsActions = (refresh: () => Promise<void>) => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [actionMessage, setActionMessage] = useState<string | null>(null);
  const refreshOperations = useCallback(async () => {
    setActionMessage(null);
    await queryClient.invalidateQueries({ queryKey: adminOperationsQueryKeys.overview() });
    await refresh();
  }, [queryClient, refresh]);
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

  const actionMutation = useMutation({
    mutationFn: async ({
      action,
    }: {
      action: () => Promise<string | void>;
      successMessage: string;
      fallback: string;
    }) => action(),
    onSuccess: async (customMessage, variables) => {
      await refreshOperations();
      setActionMessage(customMessage || variables.successMessage);
    },
    onError: (error, variables) => {
      setActionMessage(getHttpErrorMessage(error, variables.fallback));
    },
  });
  const quoteConversionMutation = useMutation({
    mutationFn: convertQuoteToOrder,
    onSuccess: (order) => {
      setActionMessage(`Devis converti en commande ${order.number}.`);
      setQuoteReference('');
      navigate(`/admin/orders/${order.id}`);
    },
    onError: (error) =>
      setQuoteConversionMessage(getHttpErrorMessage(error, 'Erreur conversion devis.')),
    onSettled: () => setQuoteConversionStatus('idle'),
  });
  const runAction = useCallback((action: () => Promise<string | void>, successMessage: string, fallback: string) => {
    actionMutation.mutate({ action, successMessage, fallback });
  }, [actionMutation]);
  const submitSupport = useCallback(() =>
    runAction(
      async () => {
        const customerId = parseNullablePositiveInteger(supportForm.customerId);
        if (customerId === null) {
          setActionMessage('ID client invalide.');
          return;
        }

        const orderId = supportForm.orderId ? parseNullablePositiveInteger(supportForm.orderId) : null;
        if (supportForm.orderId && orderId === null) {
          setActionMessage('ID de commande invalide.');
          return;
        }

        await createSupportRequest({
          customerId,
          orderId,
          subject: supportForm.subject || 'Demande SAV',
          reason: supportForm.reason,
          message: supportForm.message,
          internalNotes: supportForm.internalNotes,
        });
        setSupportForm(emptySupportForm);
      },
      'Demande SAV créée.',
      'Erreur SAV.',
    ),
    [runAction, supportForm]
  );
  const submitRefund = useCallback(() =>
    runAction(
      async () => {
        const orderId = parseNullablePositiveInteger(refundForm.orderId);
        const amountCents = parseNullablePositiveInteger(refundForm.amountCents);

        if (orderId === null || amountCents === null) {
          setActionMessage('Commande et montant requis.');
          return;
        }

        await createRefund({
          orderId,
          amountCents,
          reason: refundForm.reason,
          internalNotes: refundForm.internalNotes,
        });
        setRefundForm(emptyRefundForm);
      },
      'Suivi de remboursement créé.',
      'Erreur remboursement.',
    ),
    [runAction, refundForm],
  );
  const submitStock = useCallback(() =>
    runAction(
      async () => {
        const productId = parseNullablePositiveInteger(stockForm.productId);
        const delta = parseNullableInteger(stockForm.delta);

        if (productId === null || delta === null) {
          setActionMessage('Produit et variation requis.');
          return;
        }

        await createStockMovement({
          productId,
          delta,
          reason: stockForm.reason,
          note: stockForm.note,
        });
        setStockForm(emptyStockForm);
      },
      'Stock ajusté.',
      'Erreur stock.',
    ),
    [runAction, stockForm],
  );
  const submitBulk = useCallback(() => {
    const ids = bulkForm.orderIds
      .split(',')
      .map((value) => parseNullablePositiveInteger(value.trim()))
      .filter((id): id is number => id !== null);

    if (ids.length === 0) {
      setActionMessage('Aucune commande valide détectée.');
      return;
    }

    runAction(async () => { await bulkUpdateOrderStatus(ids, bulkForm.status); setBulkForm(emptyBulkForm); }, `${ids.length} commande(s) mise(s) à jour.`, 'Erreur action groupée.');
  }, [bulkForm, runAction]);
  const submitQuoteConversion = useCallback(() => {
    const reference = quoteReference.trim();
    if (!reference) { setQuoteConversionStatus('error'); setQuoteConversionMessage('Renseigne l’ID ou le numéro du devis.'); return; }
    setQuoteConversionStatus('loading'); setQuoteConversionMessage(null);
    quoteConversionMutation.mutate(reference);
  }, [quoteConversionMutation, quoteReference]);
  const submitShipOrder = useCallback((orderId: number) => { const payload = shippingForms[orderId] ?? emptyShippingForm; runAction(async () => { await shipFulfillmentOrder(orderId, payload); setShippingForms((current) => ({ ...current, [orderId]: emptyShippingForm })); }, 'Commande marquée comme expédiée.', 'Erreur expédition.'); }, [runAction, shippingForms]);
  const submitSupportReply = useCallback((supportId: number) => { const payload = supportReplies[supportId] ?? { subject: `Réponse SAV #${supportId}`, message: '' }; runAction(async () => { await replySupportRequest(supportId, { ...payload, status: 'waiting_customer' }); setSupportReplies((current) => ({ ...current, [supportId]: { subject: '', message: '' } })); }, 'Réponse SAV envoyée au client.', 'Erreur réponse SAV.'); }, [runAction, supportReplies]);
  const submitStripeRefund = useCallback((refundId: number) => { runAction(async () => { const refund = await processStripeRefund(refundId, { confirmation: refundConfirmations[refundId] ?? '' }); setRefundConfirmations((current) => ({ ...current, [refundId]: '' })); return `Remboursement Stripe traité : ${refund.stripeRefundId ?? 'référence Stripe créée'}.`; }, 'Remboursement Stripe traité.', 'Erreur remboursement Stripe.'); }, [refundConfirmations, runAction]);
  const submitStockThreshold = useCallback((productId: number) => {
    const threshold = parseNullableInteger(stockThresholds[productId] ?? '');
    if (threshold === null) {
      setActionMessage('Seuil de stock invalide.');
      return;
    }

    runAction(
      async () => {
        await updateLowStockThreshold(productId, threshold);
      },
      'Seuil de stock faible mis à jour.',
      'Erreur seuil stock.',
    );
  }, [runAction, stockThresholds]);
  const updateRefundStatus = useCallback((refundId: number, nextStatus: string) => { runAction(async () => { await updateRefund(refundId, { status: nextStatus }); }, 'Statut remboursement mis à jour.', 'Erreur statut remboursement.'); }, [runAction]);
  const updateSupportStatus = useCallback((supportId: number, nextStatus: string) => { runAction(async () => { await updateSupportRequest(supportId, { status: nextStatus }); }, 'Statut SAV mis à jour.', 'Erreur statut SAV.'); }, [runAction]);

  return { message: actionMessage, refresh: refreshOperations, supportForm, refundForm, stockForm, bulkForm, quoteReference, quoteConversionStatus, quoteConversionMessage, shippingForms, supportReplies, refundConfirmations, stockThresholds, setSupportForm, setRefundForm, setStockForm, setBulkForm, setQuoteReference, setShippingForms, setSupportReplies, setRefundConfirmations, setStockThresholds, submitSupport, submitRefund, submitStock, submitBulk, submitQuoteConversion, submitShipOrder, submitSupportReply, submitStripeRefund, submitStockThreshold, updateRefundStatus, updateSupportStatus };
};
