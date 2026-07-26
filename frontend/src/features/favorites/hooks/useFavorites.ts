import { useCallback, useEffect, useState } from 'react';
import { fetchFavorites, removeFavorite, type FavoriteDto } from '../api/favoritesApi';

export const useFavorites = () => {
  const [favorites, setFavorites] = useState<FavoriteDto[]>([]);
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [error, setError] = useState<string | null>(null);
  const [removingId, setRemovingId] = useState<number | null>(null);
  const refresh = useCallback(() => { setStatus('loading'); setError(null); return fetchFavorites().then((items) => { setFavorites(items); setStatus('success'); }).catch((reason: unknown) => { setError(reason instanceof Error ? reason.message : 'Une erreur est survenue en chargeant vos favoris.'); setStatus('error'); }); }, []);
  useEffect(() => { void refresh(); }, [refresh]);
  const remove = useCallback(async (productId: number) => { setRemovingId(productId); try { await removeFavorite(productId); setFavorites((current) => current.filter((favorite) => favorite.product.id !== productId)); } finally { setRemovingId(null); } }, []);
  return { favorites, status, error, removingId, refresh, remove };
};
