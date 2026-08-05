import { useEffect, useState } from 'react';
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

  const [order, setOrder] = useState<OrderDto | null>(null);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [reviewForms, setReviewForms] = useState<Record<number, ReviewFormState>>({});
  const [justConfirmed, setJustConfirmed] = useState(false);
  const [paying, setPaying] = useState(false);
  const emptyReviewForm: ReviewFormState = {
    score: 0,
    comment: '',
    submitting: false,
    error: null,
    success: false,
  };

  useEffect(() => {
    if (!orderId) return;
    setStatus('loading');
    setError(null);
    void fetchOrderById(Number(orderId))
      .then((o) => {
        setOrder(o);
        setStatus('success');
      })
      .catch((e: unknown) => {
        setError(e instanceof Error ? e.message : 'Erreur');
        setStatus('error');
      });
  }, [orderId]);

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

  const isLoading = status === 'loading';
  const canDownloadInvoice = order ? !['pending', 'cancelled'].includes(order.status) : false;

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
        comment: form.comment || undefined,
      });

      setOrder((prev) => {
        if (!prev) return prev;
        const updatedItems = prev.items.map((it) =>
          it.orderItemId === orderItemId ? { ...it, canReview: false, review } : it,
        );
        const nextPending = Math.max(0, (prev.pendingReviewsCount ?? 0) - 1);
        return {
          ...prev,
          items: updatedItems,
          pendingReviewsCount: nextPending,
          hasPendingReviews: nextPending > 0,
        };
      });

      updateReviewForm(orderItemId, { submitting: false, success: true });
    } catch (err) {
      const message = err instanceof Error ? err.message : "Impossible d'enregistrer votre avis";
      updateReviewForm(orderItemId, { submitting: false, error: message, success: false });
    }
  };

  const handlePayOrder = async () => {
    if (!order) return;

    setPaying(true);
    setError(null);

    try {
      const result = await checkoutExistingOrder(order.id);
      if ('checkoutUrl' in result) {
        redirectToTrustedUrl(result.checkoutUrl);
        return;
      }

      setOrder(result);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Impossible de lancer le règlement.');
    } finally {
      setPaying(false);
    }
  };

  const handleCancelOrder = async () => {
    if (!order) return;
    try {
      setOrder(await cancelMyOrder(order.id));
    } catch {
      /* The current order remains visible if cancellation fails. */
    }
  };
  const handleDownloadInvoicePdf = () =>
    order ? downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order)) : undefined;
  const handleDownloadInvoiceXml = () =>
    order ? downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order)) : undefined;

  return {
    canDownloadInvoice,
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
    paying,
    updateReviewForm,
  };
};
