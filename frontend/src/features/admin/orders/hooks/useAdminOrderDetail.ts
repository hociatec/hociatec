import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

import { fetchAdminOrderById, type OrderDto, type OrderEventDto, type OrderProcessingDto } from '@/features/orders/api';

const toDateInputValue = (value?: string | null) => {
  if (!value) return '';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
};

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


  return { actionMessage, canDownloadInvoice, deliveryForm, deliverySaving, error, events, order, orderId, processing, reload, setActionMessage, setDeliveryForm, setDeliverySaving, setError, status };
};
