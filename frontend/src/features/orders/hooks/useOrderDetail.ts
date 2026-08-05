import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useLocation, useNavigate, useParams } from 'react-router';

import {
  buildOrderInvoiceFilename,
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  downloadOrderInvoiceXml,
  fetchOrderById,
  submitOrderItemReview,
  type OrderDto,
} from '@/features/orders/api';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import { orderQueryKeys } from '@/shared/lib/queryKeys';
import {
  canCancelOrderStatus,
  canDownloadInvoiceForOrderStatus,
  canPayOrderStatus,
} from '@/features/orders/models/orderModel';

export type ReviewFormState = {
  score: number;
  comment: string;
  submitting: boolean;
  error: string | null;
  success: boolean;
};

export const useOrderDetail = () => {
  const { orderId } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const id = Number(orderId);

  const [reviewForms, setReviewForms] = useState<Record<number, ReviewFormState>>({});
  const [justConfirmed, setJustConfirmed] = useState(false);
  const emptyReviewForm: ReviewFormState = {
    score: 0,
    comment: '',
    submitting: false,
    error: null,
    success: false,
  };

  const orderQuery = useQuery<OrderDto, Error>({
    queryKey: orderQueryKeys.detail(Number.isFinite(id) && id > 0 ? id : null),
    queryFn: () => fetchOrderById(id),
    enabled: Number.isFinite(id) && id > 0,
  });
  const order = orderQuery.data ?? null;
  const updateOrderCache = (updated: OrderDto) => {
    queryClient.setQueryData(orderQueryKeys.detail(updated.id), updated);
    queryClient.setQueryData<OrderDto[]>(orderQueryKeys.mine(), (current = []) =>
      current.map((item) => (item.id === updated.id ? updated : item)),
    );
  };
  const payMutation = useMutation({
    mutationFn: (orderId: number) => checkoutExistingOrder(orderId),
    onSuccess: (result) => {
      if ('checkoutUrl' in result) {
        redirectToTrustedUrl(result.checkoutUrl);
        return;
      }
      updateOrderCache(result);
    },
  });
  const cancelMutation = useMutation({
    mutationFn: cancelMyOrder,
    onSuccess: updateOrderCache,
  });

  useEffect(() => {
    setReviewForms({});
  }, [order?.id]);

  useEffect(() => {
    const state = location.state as { justConfirmed?: boolean } | null;

    if (!state?.justConfirmed) {
      return;
    }

    setJustConfirmed(true);
    navigate(`${location.pathname}${location.search}`, { replace: true, state: null });
  }, [location.pathname, location.search, location.state, navigate]);

  const isLoading = orderQuery.isLoading;
  const error =
    orderQuery.error?.message ??
    (payMutation.error instanceof Error ? payMutation.error.message : null);
  const canPay = order ? canPayOrderStatus(order.status) : false;
  const canCancel = order ? canCancelOrderStatus(order.status) : false;
  const canDownloadInvoice = order ? canDownloadInvoiceForOrderStatus(order.status) : false;

  const getReviewForm = (orderItemId: number): ReviewFormState =>
    reviewForms[orderItemId] ?? emptyReviewForm;

  const updateReviewForm = (orderItemId: number, patch: Partial<ReviewFormState>) => {
    setReviewForms((prev) => {
      const current = prev[orderItemId] ?? emptyReviewForm;
      return {
        ...prev,
        [orderItemId]: {
          ...current,
          ...patch,
        },
      };
    });
  };

  const handleSubmitReview = async (orderItemId: number) => {
    if (!order) return;
    const form = getReviewForm(orderItemId);
    if (form.score < 1) {
      updateReviewForm(orderItemId, { error: 'Veuillez attribuer une note.', success: false });
      return;
    }

    updateReviewForm(orderItemId, { submitting: true, error: null, success: false });

    try {
      const review = await submitOrderItemReview(order.id, orderItemId, {
        score: form.score,
        ...(form.comment ? { comment: form.comment } : {}),
      });

      const updatedOrder = (() => {
        const updatedItems = order.items.map((it) =>
          it.orderItemId === orderItemId ? { ...it, canReview: false, review } : it,
        );
        const nextPending = Math.max(0, (order.pendingReviewsCount ?? 0) - 1);
        return {
          ...order,
          items: updatedItems,
          pendingReviewsCount: nextPending,
          hasPendingReviews: nextPending > 0,
        };
      })();
      updateOrderCache(updatedOrder);

      updateReviewForm(orderItemId, { submitting: false, success: true });
    } catch (err) {
      const message = err instanceof Error ? err.message : "Impossible d'enregistrer votre avis";
      updateReviewForm(orderItemId, { submitting: false, error: message, success: false });
    }
  };

  const handlePayOrder = async () => {
    if (!order || !canPay || payMutation.isPending) return;

    payMutation.mutate(order.id);
  };

  const handleCancelOrder = async () => {
    if (!order || !canCancel || cancelMutation.isPending) return;
    cancelMutation.mutate(order.id);
  };
  const handleDownloadInvoicePdf = () =>
    order ? downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order)) : undefined;
  const handleDownloadInvoiceXml = () =>
    order ? downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order)) : undefined;

  return {
    canDownloadInvoice,
    canPay,
    canCancel,
    error,
    getReviewForm,
    handlePayOrder,
    handleSubmitReview,
    handleCancelOrder,
    handleDownloadInvoicePdf,
    handleDownloadInvoiceXml,
    isLoading,
    justConfirmed,
    order,
    paying: payMutation.isPending,
    cancelling: cancelMutation.isPending,
    retry: orderQuery.refetch,
    updateReviewForm,
  };
};
