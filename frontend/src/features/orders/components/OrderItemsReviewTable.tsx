import { Fragment } from 'react';

import type { OrderItemDto } from '@/features/orders/api';
import type { ReviewFormState } from '@/features/orders/hooks/useOrderDetail';
import { formatEuroCents } from '@/shared/lib/formatters';

type OrderItemsReviewTableProps = {
  items: OrderItemDto[];
  getReviewForm: (orderItemId: number) => ReviewFormState;
  handleSubmitReview: (orderItemId: number) => Promise<void>;
  updateReviewForm: (orderItemId: number, patch: Partial<ReviewFormState>) => void;
};

const ReviewStarsDisplay = ({ value }: { value: number }) => (
  <span className="text-yellow-500 text-base">
    {[1, 2, 3, 4, 5].map((star) => (
      <span key={star}>{star <= Math.round(value) ? '★' : '☆'}</span>
    ))}
  </span>
);

export const OrderItemsReviewTable = ({
  items,
  getReviewForm,
  handleSubmitReview,
  updateReviewForm,
}: OrderItemsReviewTableProps) => (
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
        {items.map((item) => {
          const form = getReviewForm(item.orderItemId);
          return (
            <Fragment key={item.orderItemId ?? `${item.productSku}-${item.productName}`}>
              <tr className="border-b">
                <td className="py-2">{item.productName}</td>
                <td className="py-2">{item.productSku}</td>
                <td className="py-2">{formatEuroCents(item.unitPriceCents)}</td>
                <td className="py-2">{item.quantity}</td>
                <td className="py-2 text-right">{formatEuroCents(item.linePriceCents)}</td>
              </tr>
              {(item.review || item.canReview) && (
                <tr>
                  <td colSpan={5} id={`order-item-${item.orderItemId}`} className="py-3">
                    {item.review ? (
                      <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm">
                        <div className="flex items-center gap-2 font-semibold">
                          <ReviewStarsDisplay value={item.review.score} />
                          <span>{(item.review.score ?? 0).toFixed(1)} / 5</span>
                        </div>
                        {item.review.comment && (
                          <p className="mt-2 text-gray-700">{item.review.comment}</p>
                        )}
                      </div>
                    ) : item.canReview ? (
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
                                updateReviewForm(item.orderItemId, {
                                  score,
                                  error: null,
                                  success: false,
                                })
                              }
                              aria-label={`Attribuer ${score} etoile${score > 1 ? 's' : ''} au produit ${item.productName}`}
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
                            updateReviewForm(item.orderItemId, {
                              comment: event.target.value,
                              error: null,
                              success: false,
                            })
                          }
                        />
                        {form.error && <div className="mt-2 text-sm text-red-600">{form.error}</div>}
                        {form.success && (
                          <div className="mt-2 text-sm text-green-700">
                            Merci pour votre évaluation !
                          </div>
                        )}
                        <button
                          type="button"
                          className="register-form__submit mt-3"
                          onClick={() => void handleSubmitReview(item.orderItemId)}
                          disabled={form.submitting}
                          aria-label={`Envoyer l'evaluation du produit ${item.productName}`}
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
);
