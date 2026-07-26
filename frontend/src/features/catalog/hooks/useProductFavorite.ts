import { useCallback, useEffect, useState } from 'react';
import { addFavorite, fetchFavorites, removeFavorite } from '@/features/favorites/api/favoritesApi';
import { useAuth } from '@/features/auth/hooks/useAuth';

export const useProductFavorite = (productId?: number) => {
  const { status } = useAuth();
  const [isFavorite, setIsFavorite] = useState(false);
  const [statusState, setStatusState] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle');
  const [actionState, setActionState] = useState<'idle' | 'saving'>('idle');
  useEffect(() => {
    if (!productId || status !== 'authenticated') { setIsFavorite(false); setStatusState('idle'); return; }
    let active = true; setStatusState('loading');
    void fetchFavorites().then((items) => { if (active) { setIsFavorite(items.some((item) => item.product.id === productId)); setStatusState('ready'); } }).catch(() => { if (active) setStatusState('error'); });
    return () => { active = false; };
  }, [productId, status]);
  const toggle = useCallback(async () => {
    if (!productId) return { alreadyFavorite: false };
    setActionState('saving');
    try { if (isFavorite) { await removeFavorite(productId); setIsFavorite(false); return { alreadyFavorite: false }; } const result = await addFavorite(productId); setIsFavorite(true); return result; }
    finally { setActionState('idle'); }
  }, [isFavorite, productId]);
  return { isAuthenticated: status === 'authenticated', isFavorite, favoriteStatus: statusState, favoriteAction: actionState, toggle };
};
