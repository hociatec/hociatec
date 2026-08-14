import { useCallback, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { favoriteQueryKeys } from '@/features/favorites/queryKeys';
import {
  fetchFavoritesPage,
  removeFavoriteItem,
  type FavoriteCategory,
  type FavoriteDto,
} from '../api/favoritesApi';
import type { PaginatedResult } from '@/shared/types/api';
import { clampAtLeast } from '@/shared/lib/number';

export const useFavorites = () => {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [category, setCategory] = useState<FavoriteCategory | 'all'>('all');
  const favoritesQuery = useQuery<PaginatedResult<FavoriteDto>, Error>({
    queryKey: favoriteQueryKeys.list(page, category),
    queryFn: () => fetchFavoritesPage(page, 10, category),
  });
  const removeMutation = useMutation({
    mutationFn: ({ category, targetId }: { category: FavoriteCategory; targetId: number }) =>
      removeFavoriteItem(category, targetId),
    onMutate: async ({ category: removedCategory, targetId }) => {
      await queryClient.cancelQueries({ queryKey: favoriteQueryKeys.all() });
      const previousFavorites = queryClient.getQueryData<PaginatedResult<FavoriteDto>>(favoriteQueryKeys.list(page, category));
      queryClient.setQueryData<PaginatedResult<FavoriteDto>>(favoriteQueryKeys.list(page, category), (current) =>
        current
          ? {
              ...current,
              items: current.items.filter((favorite) => !(favorite.category === removedCategory && favorite.targetId === targetId)),
              meta: { ...current.meta, total: clampAtLeast(current.meta.total - 1, 0) },
            }
          : current,
      );

      return { previousFavorites };
    },
    onError: (_error, _payload, context) => {
      if (context?.previousFavorites) {
        queryClient.setQueryData(favoriteQueryKeys.list(page, category), context.previousFavorites);
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: favoriteQueryKeys.all() });
    },
  });
  const refresh = useCallback(() => favoritesQuery.refetch(), [favoritesQuery]);
  const remove = useCallback(
    (favoriteCategory: FavoriteCategory, targetId: number) => removeMutation.mutateAsync({ category: favoriteCategory, targetId }),
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
    category,
    setCategory,
    status,
    error,
    removingKey: removeMutation.variables ? `${removeMutation.variables.category}:${removeMutation.variables.targetId}` : null,
    refresh,
    remove,
  };
};
