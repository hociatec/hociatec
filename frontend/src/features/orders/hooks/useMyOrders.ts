import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import {
  buildOrderInvoiceFilename,
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  fetchMyOrders,
  type OrderDto,
} from '../api';
import { orderQueryKeys } from '@/shared/lib/queryKeys';

export const useMyOrders = () => {
  const [payingOrderId, setPayingOrderId] = useState<number | null>(null);
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
    mutationFn: (orderId: number) => checkoutExistingOrder(orderId),
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
  const handlePayOrder = async (orderId: number) => {
    setPayingOrderId(orderId);
    payMutation.mutate(orderId);
  };
  const handleCancelOrder = async (orderId: number) => {
    cancelMutation.mutate(orderId);
  };
  const handleDownloadInvoice = (order: OrderDto) =>
    downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order));
  return {
    orders: ordersQuery.data ?? [],
    isLoading: ordersQuery.isLoading,
    error:
      ordersQuery.error
        ? getHttpErrorMessage(ordersQuery.error, 'Erreur lors du chargement')
        : payMutation.error
          ? getHttpErrorMessage(payMutation.error, 'Impossible de lancer le règlement.')
          : null,
    payingOrderId,
    handlePayOrder,
    handleCancelOrder,
    handleDownloadInvoice,
    canDownloadInvoice: (order: OrderDto) => !['pending', 'cancelled'].includes(order.status),
  };
};
