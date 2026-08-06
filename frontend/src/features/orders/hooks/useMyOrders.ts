import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import {
  buildOrderInvoiceFilename,
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  downloadOrderInvoiceXml,
  fetchMyOrders,
  type OrderDto,
} from '../api';
import { orderQueryKeys } from '@/shared/lib/queryKeys';
import { mapOrderDtoToViewModel, type OrderViewModel } from '@/features/orders/models/orderModel';
import type { LoadState } from '@/shared/types/loadState';
import type { OrderId } from '@/shared/types/ids';
import type { PaginatedResult } from '@/shared/types/api';

export const useMyOrders = () => {
  const [page, setPage] = useState(1);
  const [payingOrderId, setPayingOrderId] = useState<OrderId | null>(null);
  const [cancellingOrderId, setCancellingOrderId] = useState<OrderId | null>(null);
  const queryClient = useQueryClient();
  const ordersQuery = useQuery<PaginatedResult<OrderDto>, Error>({
    queryKey: [...orderQueryKeys.mine(), { page }],
    queryFn: () => fetchMyOrders(page, 10),
  });
  const upsertOrderInCache = (updated: OrderDto) => {
    queryClient.setQueryData<PaginatedResult<OrderDto>>(
      [...orderQueryKeys.mine(), { page }],
      (current) =>
        current
          ? { ...current, items: current.items.map((order) => (order.id === updated.id ? updated : order)) }
          : current,
    );
    queryClient.setQueryData(orderQueryKeys.detail(updated.id), updated);
  };
  const payMutation = useMutation({
    mutationFn: (orderId: OrderId) => checkoutExistingOrder(orderId),
    onSuccess: (result) => {
      if ('checkoutUrl' in result) {
        redirectToTrustedUrl(result.checkoutUrl);
        return;
      }
      upsertOrderInCache(result);
    },
    onSettled: () => setPayingOrderId(null),
  });
  const cancelMutation = useMutation({
    mutationFn: cancelMyOrder,
    onSuccess: upsertOrderInCache,
    onSettled: () => setCancellingOrderId(null),
  });
  const handlePayOrder = async (orderId: OrderId) => {
    setPayingOrderId(orderId);
    payMutation.mutate(orderId);
  };
  const handleCancelOrder = async (orderId: OrderId) => {
    setCancellingOrderId(orderId);
    cancelMutation.mutate(orderId);
  };
  const orders = (ordersQuery.data?.items ?? []).map(mapOrderDtoToViewModel);
  const loadError = ordersQuery.error
    ? getHttpErrorMessage(ordersQuery.error, 'Erreur lors du chargement')
    : null;
  const ordersState: LoadState<OrderViewModel[]> = ordersQuery.isLoading
    ? { status: 'loading' }
    : loadError
      ? { status: 'error', error: loadError }
      : { status: 'success', data: orders };
  const handleDownloadInvoice = (order: OrderViewModel) =>
    downloadOrderInvoicePdf(order.id, order.invoiceFilename);
  const handleDownloadInvoicePdf = (order: OrderDto) =>
    downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order));
  const handleDownloadInvoiceXml = (order: OrderDto) =>
    downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order));

  return {
    orders,
    page,
    pagination: ordersQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    setPage,
    ordersState,
    isLoading: ordersState.status === 'loading',
    error: loadError
      ? loadError
      : payMutation.error
          ? getHttpErrorMessage(payMutation.error, 'Impossible de lancer le règlement.')
          : cancelMutation.error
            ? getHttpErrorMessage(cancelMutation.error, "Impossible d'annuler la commande.")
          : null,
    payingOrderId,
    cancellingOrderId,
    handlePayOrder,
    handleCancelOrder,
    handleDownloadInvoice,
    handleDownloadInvoicePdf,
    handleDownloadInvoiceXml,
    retry: ordersQuery.refetch,
  };
};
