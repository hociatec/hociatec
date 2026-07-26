import { useCallback, type Dispatch, type SetStateAction } from 'react';

import {
  addCartItem,
  applyVoucherCode as applyVoucherCodeRequest,
  clearCart as clearCartRequest,
  clearVoucherCode as clearVoucherCodeRequest,
  removeCartItem,
  updateCartItemQuantity,
} from '@/features/cart/api/cartApi';
import type { Cart, CartActionOptions } from '@/features/cart/types/cart';
import { useToast } from '@/shared/components/ui/toast';

type CartActionsDependencies = {
  setCart: Dispatch<SetStateAction<Cart | null>>;
  setError: Dispatch<SetStateAction<string | null>>;
  setPendingProductIds: Dispatch<SetStateAction<number[]>>;
  setIsClearing: Dispatch<SetStateAction<boolean>>;
  setPending: (productId: number, pending: boolean) => void;
  handleCartError: (error: unknown) => string;
};

export const useCartActions = ({
  setCart,
  setError,
  setPendingProductIds,
  setIsClearing,
  setPending,
  handleCartError,
}: CartActionsDependencies) => {
  const toast = useToast();

  const addItem = useCallback(
    async (productId: number, quantity = 1, options?: CartActionOptions) => {
      setPending(productId, true);
      try {
        setCart(await addCartItem(productId, quantity, options));
        setError(null);
      } catch (error) {
        handleCartError(error);
        throw error;
      } finally {
        setPending(productId, false);
      }
    },
    [handleCartError, setCart, setError, setPending],
  );

  const removeItem = useCallback(
    async (productId: number, options?: CartActionOptions) => {
      setPending(productId, true);
      try {
        setCart(await removeCartItem(productId, options));
        setError(null);
      } catch (error) {
        handleCartError(error);
        throw error;
      } finally {
        setPending(productId, false);
      }
    },
    [handleCartError, setCart, setError, setPending],
  );

  const setItemQuantity = useCallback(
    async (productId: number, quantity: number, options?: CartActionOptions) => {
      setPending(productId, true);
      try {
        setCart(await updateCartItemQuantity(productId, quantity, options));
        setError(null);
      } catch (error) {
        handleCartError(error);
        throw error;
      } finally {
        setPending(productId, false);
      }
    },
    [handleCartError, setCart, setError, setPending],
  );

  const clear = useCallback(async () => {
    setIsClearing(true);
    try {
      const response = await clearCartRequest();
      setPendingProductIds([]);
      setCart(response.data);
      setError(null);
      toast.show(response.message ?? 'Le panier a bien été vidé.', {
        variant: 'success',
      });
    } catch (error) {
      const message = handleCartError(error);
      toast.show(message || "Le panier n'a pas pu être vidé. Réessayez dans quelques secondes.", {
        variant: 'error',
      });
      throw error;
    } finally {
      setIsClearing(false);
    }
  }, [handleCartError, setCart, setError, setIsClearing, setPendingProductIds, toast]);

  const applyVoucherCode = useCallback(
    async (voucherCode: string) => {
      try {
        setCart(await applyVoucherCodeRequest(voucherCode));
        setError(null);
      } catch (error) {
        handleCartError(error);
        throw error;
      }
    },
    [handleCartError, setCart, setError],
  );

  const clearVoucherCode = useCallback(
    async (cartToken?: string) => {
      try {
        setCart(await clearVoucherCodeRequest(cartToken));
        setError(null);
      } catch (error) {
        handleCartError(error);
        throw error;
      }
    },
    [handleCartError, setCart, setError],
  );

  return { addItem, removeItem, setItemQuantity, clear, applyVoucherCode, clearVoucherCode };
};
