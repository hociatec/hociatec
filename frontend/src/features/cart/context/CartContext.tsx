import {
  createContext,
  useCallback,
  useMemo,
  type PropsWithChildren,
} from 'react';

import type { Cart, CartActionOptions, CartStatus } from '@/features/cart/types/cart';
import { isProductInCart as selectIsProductInCart } from './cartSelectors';
import { useCartLifecycle } from './useCartLifecycle';
import { useCartActions } from '@/features/cart/hooks/useCartActions';

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
  resetAfterCheckout: () => void;
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
  resetAfterCheckout: () => undefined,
  isProductInCart: () => false,
  isProductPending: () => false,
  isClearing: false,
};

export const CartContext = createContext<CartContextValue>(defaultValue);

export const CartProvider = ({ children }: PropsWithChildren) => {
  const {
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
  } = useCartLifecycle();

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

  const { addItem, removeItem, setItemQuantity, clear, applyVoucherCode, clearVoucherCode } =
    useCartActions({
      setCart,
      setError,
      setPendingProductIds,
      setIsClearing,
      setPending,
      handleCartError,
    });

  const isProductInCart = useCallback(
    (productId: number, options?: CartActionOptions) => selectIsProductInCart(cart, productId, options),
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
      resetAfterCheckout,
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
      resetAfterCheckout,
      refresh,
      setItemQuantity,
      status,
      isClearing,
    ],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};
