import { Fragment, useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

import { SiteLayout } from '@/shared/components/SiteLayout';
import { ErrorState, FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';

import {
  buildOrderInvoiceFilename,
  cancelMyOrder,
  checkoutExistingOrder,
  downloadOrderInvoicePdf,
  downloadOrderInvoiceXml,
  fetchOrderById,
  formatOrderStatusFr,
  submitOrderItemReview,
  type OrderDto,
} from '../api';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/shared/components/ui/alert-dialog';

type ReviewFormState = {
  score: number;
  comment: string;
  submitting: boolean;
  error: string | null;
  success: boolean;
};

export const OrderDetailPage = () => {
  const { orderId } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  useDocumentTitle('Détail de la commande');

  const [order, setOrder] = useState<OrderDto | null>(null);
  const [status, setStatus] = useState<'idle' | 'loading' | 'error' | 'success'>(
    'idle',
  );
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
      const message =
        err instanceof Error ? err.message : 'Impossible d\'enregistrer votre avis';
      updateReviewForm(orderItemId, { submitting: false, error: message, success: false });
    }
  };

  const handlePayOrder = async () => {
    if (!order) return;

    setPaying(true);
    setError(null);

    try {
      const result = await checkoutExistingOrder(order.id);
      if ('mode' in result && result.mode === 'redirect') {
        window.location.assign(result.checkoutUrl);
        return;
      }

      setOrder(result as OrderDto);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Impossible de lancer le règlement.');
    } finally {
      setPaying(false);
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
        <h1 className="mb-4 text-2xl font-semibold">Détail de la commande</h1>
        {isLoading && <LoadingState>Chargement de la commande...</LoadingState>}
        {error && <ErrorState>{error}</ErrorState>}
        {justConfirmed && (
          <FeedbackMessage variant="success" className="mb-4">
            Merci, votre commande a bien été validée et confirmée.
          </FeedbackMessage>
        )}
        {order && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <div className="font-medium">Commande {order.number}</div>
                <div className="text-sm text-gray-600">
                  Passée le {formatOptionalFrenchDate(order.createdAt)}
                </div>
                {order.appliedPromotion ? (
                  <div className="mt-2 text-sm text-green-700">
                    Réduction appliquée: {order.appliedPromotion.name}
                  </div>
                ) : null}
              </div>
              <div className="space-y-2 text-right">
                {typeof order.subtotalPriceCents === 'number' && (order.discountAmountCents ?? 0) > 0 ? (
                  <div className="text-sm text-gray-600">
                    <div>Sous-total: {formatEuroCents(order.subtotalPriceCents)}</div>
                    <div className="font-semibold text-emerald-700">
                      Remise: - {formatEuroCents(order.discountAmountCents ?? 0)}
                    </div>
                  </div>
                ) : null}
                <div className="font-semibold">{formatEuroCents(order.totalPriceCents)}</div>
                <div className="text-sm capitalize">
                  Statut: {order.statusLabel ?? formatOrderStatusFr(order.status)}
                </div>
                {order.status === 'pending' && (
                  <button
                    type="button"
                    className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
                    onClick={() => void handlePayOrder()}
                    disabled={paying}
                  >
                    {paying ? 'Redirection...' : 'Régler cette commande'}
                  </button>
                )}
                {order.status === 'pending' && (
                  <AlertDialog>
                    <AlertDialogTrigger asChild>
                      <button type="button" className="text-red-600 underline">
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

            {order.invoice && (
              <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
                <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <h2 className="font-semibold">Facture</h2>
                    {order.invoice.number ? (
                      <div className="text-sm text-stone-600">{order.invoice.number}</div>
                    ) : null}
                    {order.invoice.issuedAt ? (
                      <div className="text-sm text-stone-500">
                        Émise le {formatOptionalFrenchDate(order.invoice.issuedAt)}
                      </div>
                    ) : null}
                    <div className="text-sm text-stone-500">
                      Format électronique: {order.invoice.electronicFormat}
                    </div>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                      onClick={() => void downloadOrderInvoicePdf(order.id, buildOrderInvoiceFilename(order))}
                      disabled={!canDownloadInvoice}
                      title={!canDownloadInvoice ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}
                    >
                      Télécharger la facture PDF
                    </button>
                    <button
                      type="button"
                      className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                      onClick={() => void downloadOrderInvoiceXml(order.id, buildOrderInvoiceFilename(order))}
                      disabled={!canDownloadInvoice}
                      title={!canDownloadInvoice ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}
                    >
                      Télécharger le XML
                    </button>
                  </div>
                </div>
                <div className="text-sm">
                  <div>{order.invoice.billingName}</div>
                  {order.invoice.billingCompany ? <div>{order.invoice.billingCompany}</div> : null}
                  {order.invoice.billingCompanySiren ? <div>SIREN : {order.invoice.billingCompanySiren}</div> : null}
                  {order.invoice.billingCompanyVatNumber ? <div>TVA : {order.invoice.billingCompanyVatNumber}</div> : null}
                  {order.invoice.purchaseOrderNumber ? <div>Bon de commande : {order.invoice.purchaseOrderNumber}</div> : null}
                  <div>{order.invoice.billingAddress}</div>
                  <div>
                    {order.invoice.billingPostalCode} {order.invoice.billingCity}
                  </div>
                  {order.invoice.billingEmail ? <div>{order.invoice.billingEmail}</div> : null}
                </div>
              </div>
            )}

            <div>
              <h2 className="mb-2 font-semibold">Livraison</h2>
              <div className="text-sm">
                <div>{order.customerDisplayName || order.invoice?.billingName || order.shipping.name}</div>
                <div>{order.shipping.address}</div>
                <div>
                  {order.shipping.postalCode} {order.shipping.city}
                </div>
              </div>
              {order.delivery ? (
                <div className="mt-4 rounded-2xl border border-brand-100 bg-brand-50 p-4 text-sm text-stone-700">
                  <div className="font-semibold text-brand-900">{order.delivery.statusLabel ?? 'Préparation en cours'}</div>
                  <div className="mt-2"><span className="font-medium text-brand-900">Transporteur</span> : {order.delivery.carrier || '-'}</div>
                  <div><span className="font-medium text-brand-900">Numéro de suivi</span> : {order.delivery.trackingNumber || '-'}</div>
                  <div><span className="font-medium text-brand-900">Date estimée</span> : {formatOptionalFrenchDate(order.delivery.estimatedAt)}</div>
                  <div><span className="font-medium text-brand-900">Expédiée le</span> : {formatOptionalFrenchDate(order.delivery.shippedAt)}</div>
                  <div><span className="font-medium text-brand-900">Livrée le</span> : {formatOptionalFrenchDate(order.delivery.deliveredAt)}</div>
                  {order.delivery.trackingUrl ? (
                    <div className="mt-3">
                      <a
                        href={order.delivery.trackingUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                      >
                        Suivre le colis
                      </a>
                    </div>
                  ) : null}
                </div>
              ) : null}
            </div>

            <div>
              <h2 className="mb-2 font-semibold">Articles</h2>
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="py-2">Produit</th>
                    <th className="py-2">SKU</th>
                    <th className="py-2">Prix unitaire</th>
                    <th className="py-2">Quantité</th>
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
                          <td className="py-2">{formatEuroCents(it.unitPriceCents)}</td>
                          <td className="py-2">{it.quantity}</td>
                          <td className="py-2 text-right">{formatEuroCents(it.linePriceCents)}</td>
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
                                <div className="rounded-lg border border-brand-100 bg-white p-4 text-sm shadow-sm">
                                  <p className="mb-2 font-medium">Évaluer ce produit</p>
                                  <div className="mb-3 flex items-center gap-1">
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
                                        aria-label={`Attribuer ${score} etoile${score > 1 ? 's' : ''} au produit ${it.productName}`}
                                        aria-pressed={score === form.score}
                                      >
                                        ★
                                      </button>
                                    ))}
                                    <span className="text-xs text-gray-500">
                                      {form.score > 0 ? `${form.score}/5` : 'Choisissez une note'}
                                    </span>
                                  </div>
                                  <textarea
                                    className="w-full rounded-md border border-brand-100 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-100"
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
                                      Merci pour votre évaluation !
                                    </div>
                                  )}
                                  <button
                                    type="button"
                                    className="register-form__submit mt-3"
                                    onClick={() => void handleSubmitReview(it.orderItemId)}
                                    disabled={form.submitting}
                                    aria-label={`Envoyer l'evaluation du produit ${it.productName}`}
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
