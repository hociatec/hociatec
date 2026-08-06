import { useCallback, useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
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
} from '@/features/orders/publicApi';
import { formatApiDateForDateInput } from '@/shared/lib/formatters';
import { normalizeHttpError } from '@/shared/lib/httpClient';
import { adminOrderQueryKeys } from '@/features/admin/orders/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

const toDateInputValue = formatApiDateForDateInput;

export const useAdminOrderDetail = () => {
  const params = useParams();
  const orderId = parseNullablePositiveInteger(params.orderId);
  const isValidOrderId = orderId !== null;
  const [error, setError] = useState<string | null>(null);
  const [actionMessage, setActionMessage] = useState<string | null>(null);
  const queryClient = useQueryClient();
  const [deliveryForm, setDeliveryForm] = useState({
    status: 'preparing',
    carrier: '',
    trackingNumber: '',
    trackingUrl: '',
    estimatedAt: '',
  });

  const detailQuery = useQuery<{
    order: OrderDto;
    events: OrderEventDto[];
    processing: OrderProcessingDto;
  }, Error>({
    queryKey: adminOrderQueryKeys.detail(orderId),
    queryFn: () => fetchAdminOrderById(orderId || 0),
    enabled: isValidOrderId,
  });
  const order = detailQuery.data?.order ?? null;
  const events = detailQuery.data?.events ?? [];
  const processing = detailQuery.data?.processing ?? null;
  const status = !isValidOrderId
    ? 'error'
    : detailQuery.isLoading
      ? 'loading'
      : detailQuery.isError
        ? 'error'
        : 'success';

  useEffect(() => {
    const data = detailQuery.data;
    if (!data) return;
    setDeliveryForm({
      status: data.order.delivery?.status ?? 'preparing',
      carrier: data.order.delivery?.carrier ?? '',
      trackingNumber: data.order.delivery?.trackingNumber ?? '',
      trackingUrl: data.order.delivery?.trackingUrl ?? '',
      estimatedAt: toDateInputValue(data.order.delivery?.estimatedAt),
    });
  }, [detailQuery.data]);

  useEffect(() => {
    if (!isValidOrderId) {
      setError('Commande invalide.');
      return;
    }

    setError(detailQuery.error?.message ?? null);
  }, [detailQuery.error, orderId]);

  const reload = useCallback(async () => {
    await detailQuery.refetch();
  }, [detailQuery.refetch]);

  const invalidateDetail = useCallback(
    () =>
      queryClient.invalidateQueries({
        queryKey: adminOrderQueryKeys.detail(isValidOrderId ? orderId : null),
      }),
    [queryClient, isValidOrderId, orderId],
  );
  const invoiceMutation = useMutation({
    mutationFn: retryAdminOrderInvoice,
    onSuccess: () => void invalidateDetail(),
  });
  const resendMutation = useMutation({
    mutationFn: ({ id, scenario }: { id: number; scenario: 'order_created' | 'invoice_issued' | 'current_status' }) =>
      resendAdminOrderEmail(id, scenario),
    onSuccess: () => void invalidateDetail(),
  });
  const deliveryMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: typeof deliveryForm }) =>
      updateAdminOrderDelivery(id, payload),
    onSuccess: () => void invalidateDetail(),
  });

  const canDownloadInvoice = order ? !['pending', 'cancelled'].includes(order.status) : false;
  const runAction = useCallback(async (
    action: () => Promise<unknown>,
    successMessage: string,
    fallback: string,
  ) => {
    setActionMessage(null);
    setError(null);
    try {
      await action();
      setActionMessage(successMessage);
    } catch (e) {
      setError(e instanceof Error ? e.message : fallback);
    }
  }, [setActionMessage, setError]);
  const regenerateInvoice = useCallback(() =>
    order
      ? runAction(
          () => invoiceMutation.mutateAsync(order.id),
          'Facture regénérée.',
          'Impossible de regénérer la facture.',
        )
      : Promise.resolve(),
    [order, runAction, invoiceMutation],
  );
  const resendOrderEmail = useCallback(() =>
    order
      ? runAction(
          () => resendMutation.mutateAsync({ id: order.id, scenario: 'order_created' }),
          'Email de commande renvoyé.',
          'Impossible de renvoyer l’email.',
        )
      : Promise.resolve(),
    [order, runAction, resendMutation],
  );
  const resendStatusEmail = useCallback(() =>
    order
      ? runAction(
          () => resendMutation.mutateAsync({ id: order.id, scenario: 'current_status' }),
          'Email de statut renvoyé.',
          'Impossible de renvoyer l’email.',
        )
      : Promise.resolve(),
    [order, runAction, resendMutation],
  );
  const saveDelivery = useCallback(async () => {
    if (!order) return;
    try {
      await deliveryMutation.mutateAsync({ id: order.id, payload: deliveryForm });
      setActionMessage('Suivi livraison mis à jour.');
      setError(null);
    } catch (e) {
      const normalized = normalizeHttpError(e, 'Impossible de mettre à jour la livraison.');
      setError(
        normalized.kind === 'conflict'
          ? `${normalized.message} Rechargez la commande avant de réappliquer vos changements.`
          : normalized.message,
      );
    }
  }, [deliveryMutation, deliveryForm, order, setActionMessage, setError]);
  const downloadInvoicePdf = useCallback(
    () =>
      order
        ? downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order))
        : Promise.resolve(),
    [order],
  );
  const downloadInvoiceXml = useCallback(
    () =>
      order
        ? downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order))
        : Promise.resolve(),
    [order],
  );

  return {
    actionMessage,
    canDownloadInvoice,
    deliveryForm,
    deliverySaving: deliveryMutation.isPending,
    error,
    events,
    order,
    orderId,
    processing,
    reload,
    setActionMessage,
    setDeliveryForm,
    setDeliverySaving: () => undefined,
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
