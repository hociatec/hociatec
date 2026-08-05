import { useCallback } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { favoriteQueryKeys } from '@/shared/lib/queryKeys';
import { fetchFavorites, removeFavorite, type FavoriteDto } from '../api/favoritesApi';

export const useFavorites = () => {
  const queryClient = useQueryClient();
  const favoritesQuery = useQuery({
    queryKey: favoriteQueryKeys.all(),
    queryFn: fetchFavorites,
  });
  const removeMutation = useMutation({
    mutationFn: removeFavorite,
    onMutate: async (productId) => {
      await queryClient.cancelQueries({ queryKey: favoriteQueryKeys.all() });
      const previousFavorites = queryClient.getQueryData<FavoriteDto[]>(favoriteQueryKeys.all());
      queryClient.setQueryData<FavoriteDto[]>(favoriteQueryKeys.all(), (current = []) =>
        current.filter((favorite) => favorite.product.id !== productId),
      );

      return { previousFavorites };
    },
    onError: (_error, _productId, context) => {
      if (context?.previousFavorites) {
        queryClient.setQueryData(favoriteQueryKeys.all(), context.previousFavorites);
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
    favorites: favoritesQuery.data ?? [],
    status,
    error,
    removingId: removeMutation.variables ?? null,
    refresh,
    remove,
  };
};
