import { useCallback, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { favoriteQueryKeys } from '@/features/favorites/queryKeys';
import { fetchFavoritesPage, removeFavorite, type FavoriteDto } from '../api/favoritesApi';
import type { PaginatedResult } from '@/shared/types/api';
import { clampAtLeast } from '@/shared/lib/number';

export const useFavorites = () => {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const favoritesQuery = useQuery<PaginatedResult<FavoriteDto>, Error>({
    queryKey: [...favoriteQueryKeys.all(), { page }],
    queryFn: () => fetchFavoritesPage(page, 10),
  });
  const removeMutation = useMutation({
    mutationFn: removeFavorite,
    onMutate: async (productId) => {
      await queryClient.cancelQueries({ queryKey: favoriteQueryKeys.all() });
      const previousFavorites = queryClient.getQueryData<PaginatedResult<FavoriteDto>>([...favoriteQueryKeys.all(), { page }]);
      queryClient.setQueryData<PaginatedResult<FavoriteDto>>([...favoriteQueryKeys.all(), { page }], (current) =>
        current
          ? {
              ...current,
              items: current.items.filter((favorite) => favorite.product.id !== productId),
              meta: { ...current.meta, total: clampAtLeast(current.meta.total - 1, 0) },
            }
          : current,
      );

      return { previousFavorites };
    },
    onError: (_error, _productId, context) => {
      if (context?.previousFavorites) {
        queryClient.setQueryData([...favoriteQueryKeys.all(), { page }], context.previousFavorites);
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: favoriteQueryKeys.all() });
    },
  });
  const refresh = useCallback(() => favoritesQuery.refetch(), [favoritesQuery]);
  const remove = useCallback(
    (productId: number) => removeMutation.mutateAsync(productId),
    [removeMutation],
  );
  const status = favoritesQuery.isLoading ? 'loading' : favoritesQuery.isError ? 'error' : 'success';
  const error =
    favoritesQuery.error instanceof Error
      ? favoritesQuery.error.message
      : favoritesQuery.isError
        ? 'Une erreur est survenue en chargeant vos favoris.'
        : null;

  return {
    favorites: favoritesQuery.data?.items ?? [],
    pagination: favoritesQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    setPage,
    status,
    error,
    removingId: removeMutation.variables ?? null,
    refresh,
    remove,
  };
};
