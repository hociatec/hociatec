import { useCallback } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { useAuth } from '@/features/auth/hooks/useAuth';
import type { CatalogProduct } from '@/features/catalog/api';
import {
  addFavorite,
  fetchFavorites,
  removeFavorite,
  type FavoriteDto,
} from '@/features/favorites/api/favoritesApi';
import { favoriteQueryKeys } from '@/shared/lib/queryKeys';

export const useProductFavorite = (product?: CatalogProduct | null) => {
  const { status } = useAuth();
  const queryClient = useQueryClient();
  const productId = product?.id;
  const favoritesQuery = useQuery({
    queryKey: favoriteQueryKeys.all(),
    queryFn: fetchFavorites,
    enabled: Boolean(productId && status === 'authenticated'),
  });
  const isFavorite = Boolean(
    productId && favoritesQuery.data?.some((item) => item.product.id === productId),
  );
  const removeMutation = useMutation({
    mutationFn: removeFavorite,
    onMutate: async (targetProductId) => {
      await queryClient.cancelQueries({ queryKey: favoriteQueryKeys.all() });
      const previousFavorites = queryClient.getQueryData<FavoriteDto[]>(favoriteQueryKeys.all());
      queryClient.setQueryData<FavoriteDto[]>(favoriteQueryKeys.all(), (current = []) =>
        current.filter((favorite) => favorite.product.id !== targetProductId),
      );

      return { previousFavorites };
    },
    onError: (_error, _targetProductId, context) => {
      if (context?.previousFavorites) {
        queryClient.setQueryData(favoriteQueryKeys.all(), context.previousFavorites);
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: favoriteQueryKeys.all() });
    },
  });
  const addMutation = useMutation({
    mutationFn: addFavorite,
    onMutate: async (targetProductId) => {
      await queryClient.cancelQueries({ queryKey: favoriteQueryKeys.all() });
      const previousFavorites = queryClient.getQueryData<FavoriteDto[]>(favoriteQueryKeys.all());
      if (product && product.id === targetProductId) {
        queryClient.setQueryData<FavoriteDto[]>(favoriteQueryKeys.all(), (current = []) =>
          current.some((favorite) => favorite.product.id === targetProductId)
            ? current
            : [{ addedAt: new Date().toISOString(), product }, ...current],
        );
      }

      return { previousFavorites };
    },
    onSuccess: (result, targetProductId) => {
      queryClient.setQueryData<FavoriteDto[]>(favoriteQueryKeys.all(), (current = []) => {
        const withoutTarget = current.filter((favorite) => favorite.product.id !== targetProductId);
        return [result.favorite, ...withoutTarget];
      });
    },
    onError: (_error, _targetProductId, context) => {
      if (context?.previousFavorites) {
        queryClient.setQueryData(favoriteQueryKeys.all(), context.previousFavorites);
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: favoriteQueryKeys.all() });
    },
  });
  const toggle = useCallback(async () => {
    if (!productId) return { alreadyFavorite: false };
    if (isFavorite) {
      await removeMutation.mutateAsync(productId);
      return { alreadyFavorite: false };
    }

    return addMutation.mutateAsync(productId);
  }, [addMutation, isFavorite, productId, removeMutation]);
  const favoriteStatus: 'idle' | 'loading' | 'ready' | 'error' =
    status !== 'authenticated' || !productId
      ? 'idle'
      : favoritesQuery.isLoading
        ? 'loading'
        : favoritesQuery.isError
          ? 'error'
          : 'ready';

  return {
    isAuthenticated: status === 'authenticated',
    isFavorite,
    favoriteStatus,
    favoriteAction: addMutation.isPending || removeMutation.isPending ? 'saving' : 'idle',
    toggle,
  };
};
