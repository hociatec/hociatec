import { useEffect, useState } from 'react';
import { useParams } from 'react-router';

import {
  buildOrderInvoiceFilename,
  downloadOrderInvoicePdf,
  downloadOrderInvoiceXml,
  fetchAdminOrderById,
  resendAdminOrderEmail,
  retryAdminOrderInvoice,
  updateAdminOrderDelivery,
  type OrderDto,
  type OrderEventDto,
  type OrderProcessingDto,
} from '@/features/orders/api';
import { formatApiDateForDateInput } from '@/shared/lib/formatters';

const toDateInputValue = formatApiDateForDateInput;

export const useAdminOrderDetail = () => {
  const params = useParams();
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

  const canDownloadInvoice = order ? !['pending', 'cancelled'].includes(order.status) : false;
  const runAction = async (
    action: () => Promise<unknown>,
    successMessage: string,
    fallback: string,
  ) => {
    setActionMessage(null);
    setError(null);
    try {
      await action();
      await reload();
      setActionMessage(successMessage);
    } catch (e) {
      setError(e instanceof Error ? e.message : fallback);
    }
  };
  const regenerateInvoice = () =>
    order
      ? runAction(
          () => retryAdminOrderInvoice(order.id),
          'Facture regénérée.',
          'Impossible de regénérer la facture.',
        )
      : Promise.resolve();
  const resendOrderEmail = () =>
    order
      ? runAction(
          () => resendAdminOrderEmail(order.id, 'order_created'),
          'Email de commande renvoyé.',
          'Impossible de renvoyer l’email.',
        )
      : Promise.resolve();
  const resendStatusEmail = () =>
    order
      ? runAction(
          () => resendAdminOrderEmail(order.id, 'current_status'),
          'Email de statut renvoyé.',
          'Impossible de renvoyer l’email.',
        )
      : Promise.resolve();
  const saveDelivery = async () => {
    if (!order) return;
    setDeliverySaving(true);
    try {
      await updateAdminOrderDelivery(order.id, deliveryForm);
      await reload();
      setActionMessage('Suivi livraison mis à jour.');
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Impossible de mettre à jour la livraison.');
    } finally {
      setDeliverySaving(false);
    }
  };
  const downloadInvoicePdf = () =>
    order ? downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order)) : Promise.resolve();
  const downloadInvoiceXml = () =>
    order ? downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order)) : Promise.resolve();

  return {
    actionMessage,
    canDownloadInvoice,
    deliveryForm,
    deliverySaving,
    error,
    events,
    order,
    orderId,
    processing,
    reload,
    setActionMessage,
    setDeliveryForm,
    setDeliverySaving,
    setError,
    status,
    regenerateInvoice,
    resendOrderEmail,
    resendStatusEmail,
    saveDelivery,
    downloadInvoicePdf,
    downloadInvoiceXml,
  };
};
