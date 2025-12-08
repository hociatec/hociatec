import { Fragment, useEffect, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import { cancelMyOrder, fetchOrderById, formatOrderStatusFr, submitOrderItemReview, type OrderDto } from '../api';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/shared/components/ui/alert-dialog';

type ReviewFormState = {
  score: number;
  comment: string;
  submitting: boolean;
  error: string | null;
  success: boolean;
};

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(
    valueInCents / 100,
  );

export const OrderDetailPage = () => {
  const { orderId } = useParams();
  const [params] = useSearchParams();
  useDocumentTitle('Detail de la commande');

  const [order, setOrder] = useState<OrderDto | null>(null);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>(
    'idle',
  );
  const [error, setError] = useState<string | null>(null);
  const [reviewForms, setReviewForms] = useState<Record<number, ReviewFormState>>({});
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
    if (!order) return;
    const targetReview = params.get('review');
    if (!targetReview) return;
    const element = document.getElementById(`order-item-${targetReview}`);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
      element.classList.add('ring-2', 'ring-blue-300');
      window.setTimeout(() => {
        element.classList.remove('ring-2', 'ring-blue-300');
      }, 2000);
    }
  }, [order, params]);

  useEffect(() => {
    setReviewForms({});
  }, [order?.id]);

  const isLoading = status === 'loading';
  const justConfirmed = params.get('confirmed') === '1';

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
      const message =
        err instanceof Error ? err.message : 'Impossible d\'enregistrer votre avis';
      updateReviewForm(orderItemId, { submitting: false, error: message, success: false });
    }
  };

  const ReviewStarsDisplay = ({ value }: { value: number }) => (
    <span className="text-yellow-500 text-base">
      {[1, 2, 3, 4, 5].map((star) => (
        <span key={star}>{star <= Math.round(value) ? '★' : '☆'}</span>
      ))}
    </span>
  );

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-2xl font-semibold mb-4">Detail de la commande</h1>
        {isLoading && <p>Chargement...</p>}
        {error && <div className="text-red-600">{error}</div>}
        {justConfirmed && (
          <div className="mb-4 p-3 rounded bg-green-50 text-green-800">
            Merci, votre commande a bien ete enregistree.
          </div>
        )}
        {order && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <div className="font-medium">Commande {order.number}</div>
                <div className="text-sm text-gray-600">
                  Passee le {new Date(order.createdAt).toLocaleDateString('fr-FR')}
                </div>
              </div>
              <div className="text-right space-y-2">
                <div className="font-semibold">{formatPrice(order.totalPriceCents)}</div>
                <div className="text-sm capitalize">Statut: {order.statusLabel ?? formatOrderStatusFr(order.status)}</div>
                {order.status === 'pending' && (
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <button type="button" className="underline text-red-600">
                        Annuler la commande
                      </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Confirmer l'annulation</AlertDialogTitle>
                        <AlertDialogDescription>
                          Voulez-vous annuler cette commande en attente ? Cette action est irréversible.
                        </AlertDialogDescription>
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Non</AlertDialogCancel>
                        <AlertDialogAction
                          onClick={() => {
                            if (!order) return;
                            void cancelMyOrder(order.id)
                              .then((updated) => setOrder(updated))
                              .catch(() => undefined);
                          }}
                        >
                          Oui, annuler
                        </AlertDialogAction>
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                )}
              </div>
            </div>

            <div>
              <h2 className="font-semibold mb-2">Livraison</h2>
              <div className="text-sm">
                <div>{order.shipping.name}</div>
                <div>{order.shipping.address}</div>
                <div>
                  {order.shipping.postalCode} {order.shipping.city}
                </div>
              </div>
            </div>

            <div>
              <h2 className="font-semibold mb-2">Articles</h2>
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left border-b">
                    <th className="py-2">Produit</th>
                    <th className="py-2">SKU</th>
                    <th className="py-2">Prix unitaire</th>
                    <th className="py-2">Quantite</th>
                    <th className="py-2 text-right">Sous-total</th>
                  </tr>
                </thead>
                <tbody>
                  {order.items.map((it) => {
                    const form = getReviewForm(it.orderItemId);
                    return (
                      <Fragment key={it.orderItemId ?? `${it.productSku}-${it.productName}`}>
                        <tr className="border-b">
                          <td className="py-2">{it.productName}</td>
                          <td className="py-2">{it.productSku}</td>
                          <td className="py-2">{formatPrice(it.unitPriceCents)}</td>
                          <td className="py-2">{it.quantity}</td>
                          <td className="py-2 text-right">{formatPrice(it.linePriceCents)}</td>
                        </tr>
                        {(it.review || it.canReview) && (
                          <tr>
                            <td colSpan={5} id={`order-item-${it.orderItemId}`} className="py-3">
                              {it.review ? (
                                <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm">
                                  <div className="flex items-center gap-2 font-semibold">
                                    <ReviewStarsDisplay value={it.review.score} />
                                    <span>{(it.review.score ?? 0).toFixed(1)} / 5</span>
                                  </div>
                                  {it.review.comment && (
                                    <p className="mt-2 text-gray-700">{it.review.comment}</p>
                                  )}
                                </div>
                              ) : it.canReview ? (
                                <div className="rounded-lg border border-slate-200 bg-white p-4 text-sm shadow-sm">
                                  <p className="font-medium mb-2">Donner votre avis</p>
                                  <div className="flex items-center gap-1 mb-3">
                                    {[1, 2, 3, 4, 5].map((score) => (
                                      <button
                                        key={score}
                                        type="button"
                                        className={`text-2xl leading-none ${
                                          score <= form.score ? 'text-yellow-500' : 'text-gray-300'
                                        }`}
                                        onClick={() =>
                                          updateReviewForm(it.orderItemId, {
                                            score,
                                            error: null,
                                            success: false,
                                          })
                                        }
                                      >
                                        ★
                                      </button>
                                    ))}
                                    <span className="text-xs text-gray-500">
                                      {form.score > 0 ? `${form.score}/5` : 'Choisissez une note'}
                                    </span>
                                  </div>
                                  <textarea
                                    className="w-full rounded-md border border-slate-200 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300"
                                    placeholder="Partagez votre experience..."
                                    rows={3}
                                    value={form.comment}
                                    onChange={(event) =>
                                      updateReviewForm(it.orderItemId, {
                                        comment: event.target.value,
                                        error: null,
                                        success: false,
                                      })
                                    }
                                  />
                                  {form.error && (
                                    <div className="mt-2 text-sm text-red-600">{form.error}</div>
                                  )}
                                  {form.success && (
                                    <div className="mt-2 text-sm text-green-700">
                                      Merci pour votre avis !
                                    </div>
                                  )}
                                  <button
                                    type="button"
                                    className="register-form__submit mt-3"
                                    onClick={() => void handleSubmitReview(it.orderItemId)}
                                    disabled={form.submitting}
                                  >
                                    {form.submitting ? 'Envoi...' : 'Envoyer mon avis'}
                                  </button>
                                </div>
                              ) : null}
                            </td>
                          </tr>
                        )}
                      </Fragment>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
