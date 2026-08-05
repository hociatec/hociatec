import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import {
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  fetchMyOrders,
  type OrderDto,
} from '../api';
import { orderQueryKeys } from '@/shared/lib/queryKeys';
import { mapOrderDtoToViewModel, type OrderViewModel } from '@/features/orders/models/orderModel';
import type { LoadState } from '@/shared/types/loadState';
import type { OrderId } from '@/shared/types/ids';

export const useMyOrders = () => {
  const [payingOrderId, setPayingOrderId] = useState<OrderId | null>(null);
  const queryClient = useQueryClient();
  const ordersQuery = useQuery<OrderDto[], Error>({
    queryKey: orderQueryKeys.mine(),
    queryFn: fetchMyOrders,
  });
  const upsertOrderInCache = (updated: OrderDto) => {
    queryClient.setQueryData<OrderDto[]>(orderQueryKeys.mine(), (current = []) =>
      current.map((order) => (order.id === updated.id ? updated : order)),
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
  });
  const handlePayOrder = async (orderId: OrderId) => {
    setPayingOrderId(orderId);
    payMutation.mutate(orderId);
  };
  const handleCancelOrder = async (orderId: OrderId) => {
    cancelMutation.mutate(orderId);
  };
  const orders = (ordersQuery.data ?? []).map(mapOrderDtoToViewModel);
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

  return {
    orders,
    ordersState,
    isLoading: ordersState.status === 'loading',
    error: loadError
      ? loadError
      : payMutation.error
          ? getHttpErrorMessage(payMutation.error, 'Impossible de lancer le règlement.')
          : null,
    payingOrderId,
    handlePayOrder,
    handleCancelOrder,
    handleDownloadInvoice,
    retry: ordersQuery.refetch,
  };
};
