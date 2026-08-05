import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';

import { fetchProductReviews, type CatalogProduct, type ProductPublicReview } from '../api';
import { catalogQueryKeys } from '@/shared/lib/queryKeys';

const REVIEWS_PER_PAGE = 5;

export const useProductReviews = (product: CatalogProduct | null) => {
  const reviewsQuery = useInfiniteQuery({
    queryKey: catalogQueryKeys.productReviews(product?.slug ?? null, REVIEWS_PER_PAGE),
    queryFn: ({ pageParam }) =>
      fetchProductReviews(product?.slug ?? '', { page: pageParam, perPage: REVIEWS_PER_PAGE }),
    enabled: Boolean(product),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const nextPage = lastPage.meta.page + 1;
      return lastPage.meta.total > lastPage.meta.page * lastPage.meta.perPage ? nextPage : undefined;
    },
  });
  const reviews = useMemo<ProductPublicReview[]>(
    () => reviewsQuery.data?.pages.flatMap((page) => page.items) ?? [],
    [reviewsQuery.data],
  );
  const fallbackMeta = { total: product?.reviews?.count ?? 0, average: product?.reviews?.average ?? 0 };
  const currentMeta = reviewsQuery.data?.pages.at(-1)?.meta;

  return {
    hasMoreReviews: reviewsQuery.hasNextPage,
    loadMoreReviews: () => {
      void reviewsQuery.fetchNextPage();
    },
    reviews,
    reviewsError: reviewsQuery.error?.message ?? null,
    reviewsLoading: reviewsQuery.isLoading || reviewsQuery.isFetchingNextPage,
    reviewsMeta: currentMeta
      ? { total: currentMeta.total, average: currentMeta.average }
      : fallbackMeta,
  };
};
