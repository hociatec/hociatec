import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useState,
  type PropsWithChildren,
} from 'react';

import {
  addCartItem,
  applyVoucherCode as applyVoucherCodeRequest,
  CartApiError,
  clearCart as clearCartRequest,
  clearVoucherCode as clearVoucherCodeRequest,
  fetchCart,
  removeCartItem,
  updateCartItemQuantity,
} from '@/features/cart/api';
import type { Cart, CartStatus } from '@/features/cart/types';
import {
  clearCartToken,
  getPersistedCartToken,
} from '@/shared/lib/httpClient';
import { useToast } from '@/shared/components/ui/toast';

type CartActionOptions = {
  rentalMonths?: number;
  currentRentalMonths?: number;
};

interface CartContextValue {
  cart: Cart | null;
  status: CartStatus;
  error: string | null;
  addItem: (productId: number, quantity?: number, options?: CartActionOptions) => Promise<void>;
  removeItem: (productId: number, options?: CartActionOptions) => Promise<void>;
  setItemQuantity: (
    productId: number,
    quantity: number,
    options?: CartActionOptions,
  ) => Promise<void>;
  clear: () => Promise<void>;
  applyVoucherCode: (voucherCode: string) => Promise<void>;
  clearVoucherCode: (cartToken?: string) => Promise<void>;
  refresh: () => Promise<void>;
  isProductInCart: (productId: number, options?: CartActionOptions) => boolean;
  isProductPending: (productId: number) => boolean;
  isClearing: boolean;
}

const rejectedPromise = async () => {
  throw new Error('CartProvider not mounted');
};

const defaultValue: CartContextValue = {
  cart: null,
  status: 'idle',
  error: null,
  addItem: rejectedPromise,
  removeItem: rejectedPromise,
  setItemQuantity: rejectedPromise,
  clear: rejectedPromise,
  applyVoucherCode: rejectedPromise,
  clearVoucherCode: rejectedPromise,
  refresh: rejectedPromise,
  isProductInCart: () => false,
  isProductPending: () => false,
  isClearing: false,
};

export const CartContext = createContext<CartContextValue>(defaultValue);

export const CartProvider = ({ children }: PropsWithChildren) => {
  const [cart, setCart] = useState<Cart | null>(null);
  const [status, setStatus] = useState<CartStatus>('idle');
  const [error, setError] = useState<string | null>(null);
  const [pendingProductIds, setPendingProductIds] = useState<number[]>([]);
  const [isClearing, setIsClearing] = useState(false);
  const toast = useToast();

  const setPending = useCallback((productId: number, pending: boolean) => {
    setPendingProductIds((previous) => {
      const next = new Set(previous);
      if (pending) {
        next.add(productId);
      } else {
        next.delete(productId);
      }
      return Array.from(next);
    });
  }, []);

  const handleCartError = useCallback((err: unknown) => {
    const message =
      err instanceof Error
        ? err.message
        : 'Une erreur est survenue lors de la mise à jour du panier.';

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

  useEffect(() => {
    const initializeCart = async () => {
      const existingToken = getPersistedCartToken();

      if (!existingToken) {
        setStatus('ready');
        setCart(null);
        setError(null);
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
    };

    void initializeCart();
  }, [handleCartError, toast]);

  const addItem = useCallback(
    async (productId: number, quantity = 1, options?: CartActionOptions) => {
      setPending(productId, true);
      try {
        const updatedCart = await addCartItem(productId, quantity, options);
        setCart(updatedCart);
        setError(null);
      } catch (err) {
        handleCartError(err);
        throw err;
      } finally {
        setPending(productId, false);
      }
    },
    [handleCartError, setPending],
  );

  const removeItem = useCallback(
    async (productId: number, options?: CartActionOptions) => {
      setPending(productId, true);
      try {
        const updatedCart = await removeCartItem(productId, options);
        setCart(updatedCart);
        setError(null);
      } catch (err) {
        handleCartError(err);
        throw err;
      } finally {
        setPending(productId, false);
      }
    },
    [handleCartError, setPending],
  );

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

  const setItemQuantity = useCallback(
    async (productId: number, quantity: number, options?: CartActionOptions) => {
      setPending(productId, true);
      try {
        const updatedCart = await updateCartItemQuantity(productId, quantity, options);
        setCart(updatedCart);
        setError(null);
      } catch (err) {
        handleCartError(err);
        throw err;
      } finally {
        setPending(productId, false);
      }
    },
    [handleCartError, setPending],
  );

  const clear = useCallback(async () => {
    setIsClearing(true);
    try {
      const updatedCart = await clearCartRequest();
      setPendingProductIds([]);
      setCart(updatedCart);
      setError(null);
      toast.show('Panier vidé avec succès.', { variant: 'success' });
    } catch (err) {
      const message = handleCartError(err);
      toast.show(message || 'Impossible de vider le panier.', { variant: 'error' });
      throw err;
    } finally {
      setIsClearing(false);
    }
  }, [handleCartError, toast]);

  const applyVoucherCode = useCallback(async (voucherCode: string) => {
    try {
      const updatedCart = await applyVoucherCodeRequest(voucherCode);
      setCart(updatedCart);
      setError(null);
    } catch (err) {
      handleCartError(err);
      throw err;
    }
  }, [handleCartError]);

  const clearVoucherCode = useCallback(async (cartToken?: string) => {
    try {
      const updatedCart = await clearVoucherCodeRequest(cartToken);
      setCart(updatedCart);
      setError(null);
    } catch (err) {
      handleCartError(err);
      throw err;
    }
  }, [handleCartError]);

  const isProductInCart = useCallback(
    (productId: number, options?: CartActionOptions) => {
      if (!cart) {
        return false;
      }

      const wantedMonths = options?.rentalMonths ?? options?.currentRentalMonths;

      return cart.items.some((item) => {
        if (item.product.id !== productId) {
          return false;
        }

        if (item.product.sellingType !== 'rental' || wantedMonths === undefined) {
          return true;
        }

        const currentMonths = Math.max(1, item.rentalMonths ?? 1);
        return currentMonths === Math.max(1, wantedMonths);
      });
    },
    [cart],
  );

  const isProductPending = useCallback(
    (productId: number) => pendingProductIds.includes(productId),
    [pendingProductIds],
  );

  const value = useMemo(
    () => ({
      cart,
      status,
      error,
      addItem,
      removeItem,
      setItemQuantity,
      clear,
      applyVoucherCode,
      clearVoucherCode,
      refresh,
      isProductInCart,
      isProductPending,
      isClearing,
    }),
    [
      addItem,
      applyVoucherCode,
      clear,
      clearVoucherCode,
      cart,
      error,
      isProductInCart,
      isProductPending,
      refresh,
      setItemQuantity,
      status,
      isClearing,
    ],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};
