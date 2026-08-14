import { useCallback } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { useAuth } from '@/features/auth/publicApi';
import {
  addFavoriteItem,
  fetchFavoriteStatus,
  removeFavoriteItem,
  type FavoriteCategory,
} from '@/features/favorites/api/favoritesApi';
import { favoriteQueryKeys } from '@/features/favorites/queryKeys';

export const useFavoriteItem = (category: FavoriteCategory, targetId?: number | null) => {
  const { status } = useAuth();
  const queryClient = useQueryClient();
  const isAuthenticated = status === 'authenticated';
  const statusQuery = useQuery({
    queryKey: targetId ? favoriteQueryKeys.status(category, targetId) : [...favoriteQueryKeys.all(), 'status', category, 'missing'],
    queryFn: () => fetchFavoriteStatus(category, targetId ?? 0),
    enabled: Boolean(targetId && isAuthenticated),
  });

  const addMutation = useMutation({
    mutationFn: () => addFavoriteItem(category, targetId ?? 0),
    onSuccess: () => {
      if (targetId) {
        queryClient.setQueryData(favoriteQueryKeys.status(category, targetId), {
          category,
          targetId,
          isFavorite: true,
        });
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: favoriteQueryKeys.all() });
    },
  });

  const removeMutation = useMutation({
    mutationFn: () => removeFavoriteItem(category, targetId ?? 0),
    onSuccess: () => {
      if (targetId) {
        queryClient.setQueryData(favoriteQueryKeys.status(category, targetId), {
          category,
          targetId,
          isFavorite: false,
        });
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: favoriteQueryKeys.all() });
    },
  });

  const toggle = useCallback(async () => {
    if (!targetId) {
      return { alreadyFavorite: false };
    }

    if (statusQuery.data?.isFavorite) {
      await removeMutation.mutateAsync();
      return { alreadyFavorite: false };
    }

    return addMutation.mutateAsync();
  }, [addMutation, removeMutation, statusQuery.data?.isFavorite, targetId]);

  return {
    isAuthenticated,
    isFavorite: Boolean(statusQuery.data?.isFavorite),
    favoriteStatus: !isAuthenticated || !targetId ? 'idle' : statusQuery.isLoading ? 'loading' : statusQuery.isError ? 'error' : 'ready',
    favoriteAction: addMutation.isPending || removeMutation.isPending ? 'saving' : 'idle',
    toggle,
  } as const;
};
