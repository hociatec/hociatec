import { useCallback, useEffect, useState } from 'react';

import { CartApiError, fetchCart } from '@/features/cart/api/cartApi';
import type { Cart, CartStatus } from '@/features/cart/types/cart';
import { clearCartToken, getPersistedCartToken } from '@/shared/lib/httpClient';

export const useCartLifecycle = () => {
  const [cart, setCart] = useState<Cart | null>(null);
  const [status, setStatus] = useState<CartStatus>('idle');
  const [error, setError] = useState<string | null>(null);
  const [pendingProductIds, setPendingProductIds] = useState<number[]>([]);
  const [isClearing, setIsClearing] = useState(false);

  const handleCartError = useCallback((err: unknown) => {
    const message =
      err instanceof Error
        ? err.message
        : "Le panier n'a pas pu être mis à jour. Réessayez dans quelques secondes.";

    if (
      err instanceof CartApiError &&
      (err.code === 'cart_not_found' || err.code === 'token_missing')
    ) {
      clearCartToken();
      setCart(null);
    }

    setError(message);

    return message;
  }, []);

  const refresh = useCallback(async () => {
    const existingToken = getPersistedCartToken();

    if (!existingToken) {
      setCart(null);
      setError(null);
      setStatus('ready');
      return;
    }

    setStatus('loading');
    try {
      const currentCart = await fetchCart();
      setCart(currentCart);
      setError(null);
    } catch (err) {
      handleCartError(err);
    } finally {
      setStatus('ready');
    }
  }, [handleCartError]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const resetAfterCheckout = useCallback(() => {
    clearCartToken();
    setPendingProductIds([]);
    setCart(null);
    setError(null);
    setStatus('ready');
  }, []);

  return {
    cart,
    error,
    handleCartError,
    isClearing,
    pendingProductIds,
    refresh,
    resetAfterCheckout,
    setCart,
    setError,
    setIsClearing,
    setPendingProductIds,
    status,
  };
};
