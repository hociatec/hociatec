import { useEffect, useState } from 'react';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import {
  buildOrderInvoiceFilename,
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  fetchMyOrders,
  type OrderDto,
} from '../api';

export const useMyOrders = () => {
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [payingOrderId, setPayingOrderId] = useState<number | null>(null);
  useEffect(() => {
    setStatus('loading');
    void fetchMyOrders()
      .then((items) => {
        setOrders(items);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(getHttpErrorMessage(e, 'Erreur lors du chargement'));
        setStatus('error');
      });
  }, []);
  const handlePayOrder = async (orderId: number) => {
    setPayingOrderId(orderId);
    setError(null);
    try {
      const result = await checkoutExistingOrder(orderId);
      if ('checkoutUrl' in result) {
        window.location.assign(result.checkoutUrl);
        return;
      }
      setOrders((previous) => previous.map((order) => (order.id === orderId ? result : order)));
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de lancer le règlement.'));
    } finally {
      setPayingOrderId(null);
    }
  };
  const handleCancelOrder = async (orderId: number) => {
    try {
      const cancelledOrder = await cancelMyOrder(orderId);
      setOrders((previous) =>
        previous.map((order) => (order.id === orderId ? cancelledOrder : order)),
      );
    } catch {
      /* The dialog remains closed; the next refresh exposes the current status. */
    }
  };
  const handleDownloadInvoice = (order: OrderDto) =>
    downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order));
  return {
    orders,
    isLoading: status === 'loading',
    error,
    payingOrderId,
    handlePayOrder,
    handleCancelOrder,
    handleDownloadInvoice,
    canDownloadInvoice: (order: OrderDto) => !['pending', 'cancelled'].includes(order.status),
  };
};
