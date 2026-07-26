import { useCallback, useEffect, useState } from 'react';

import { fetchProductReviews, type CatalogProduct, type ProductPublicReview } from '../api';

const REVIEWS_PER_PAGE = 5;

export const useProductReviews = (product: CatalogProduct | null) => {
  const [reviews, setReviews] = useState<ProductPublicReview[]>([]);
  const [reviewsMeta, setReviewsMeta] = useState({ total: 0, average: 0 });
  const [reviewsLoading, setReviewsLoading] = useState(false);
  const [reviewsError, setReviewsError] = useState<string | null>(null);
  const [reviewsPage, setReviewsPage] = useState(1);
  const [hasMoreReviews, setHasMoreReviews] = useState(false);

  const loadReviews = useCallback(
    (page = 1, append = false) => {
      if (!product) return;
      setReviewsLoading(true);
      setReviewsError(null);

      void fetchProductReviews(product.slug, { page, perPage: REVIEWS_PER_PAGE })
        .then((response) => {
          const meta = response?.meta ?? { total: 0, average: 0 };
          setReviewsMeta({ total: meta.total, average: meta.average });
          setReviews((previous) => {
            const next = append
              ? [...previous, ...(response?.items ?? [])]
              : (response?.items ?? []);
            setHasMoreReviews(meta.total > next.length);
            return next;
          });
          setReviewsPage(page);
        })
        .catch((err: Error) => setReviewsError(err.message || 'Impossible de charger les avis.'))
        .finally(() => setReviewsLoading(false));
    },
    [product],
  );

  useEffect(() => {
    if (!product) return;
    setReviews([]);
    setReviewsMeta({ total: product.reviews?.count ?? 0, average: product.reviews?.average ?? 0 });
    setHasMoreReviews(false);
    setReviewsPage(1);
    loadReviews(1);
  }, [product?.slug, loadReviews]);

  return {
    hasMoreReviews,
    loadMoreReviews: () => loadReviews(reviewsPage + 1, true),
    reviews,
    reviewsError,
    reviewsLoading,
    reviewsMeta,
  };
};
