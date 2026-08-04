import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import type { ProductPublicReview } from '../api';
import { RatingStars } from './RatingStars';

interface ProductReviewsSectionProps {
  hasMoreReviews: boolean;
  onLoadMoreReviews: () => void;
  reviews: ProductPublicReview[];
  reviewsError: string | null;
  reviewsLoading: boolean;
  summaryAverage: number;
  summaryCount: number;
}

export const ProductReviewsSection = ({
  hasMoreReviews,
  onLoadMoreReviews,
  reviews,
  reviewsError,
  reviewsLoading,
  summaryAverage,
  summaryCount,
}: ProductReviewsSectionProps) => (
  <section className="catalog-reviews-section">
    <div className="catalog-reviews-card">
      <div className="catalog-reviews-card__header">
        <div>
          <h2>Avis clients</h2>
          <p className="muted">Ce que disent les clients ayant commandé ce produit.</p>
        </div>
        <div className="catalog-review-badge catalog-review-badge--summary">
          <RatingStars value={summaryAverage} />
          <div>
            <strong>{summaryAverage.toFixed(1)} / 5</strong>
            <span className="muted">
              {summaryCount} avis{summaryCount > 1 ? 's' : ''}
            </span>
          </div>
        </div>
      </div>
      {reviewsLoading && <p className="sr-only">Chargement des avis...</p>}
      {reviewsError && <p className="muted">{reviewsError}</p>}
      {!reviewsLoading && reviews.length === 0 && (
        <p className="muted">Pas encore d'avis pour ce produit.</p>
      )}
      <ul className="catalog-reviews-list">
        {reviews.map((review) => (
          <li key={review.id} className="catalog-review">
            <div className="catalog-review__header">
              <RatingStars value={review.score} />
              <div>
                <strong>{review.author.displayName}</strong>
                <span className="muted">{formatOptionalFrenchDate(review.createdAt)}</span>
              </div>
            </div>
            {review.comment && <p>{review.comment}</p>}
          </li>
        ))}
      </ul>
      {hasMoreReviews && (
        <button
          type="button"
          className="catalog-review__load-more"
          onClick={onLoadMoreReviews}
          disabled={reviewsLoading}
        >
          Charger plus d'avis
        </button>
      )}
    </div>
  </section>
);
