import type { Cart, CartActionOptions } from '@/features/cart/types/cart';
import { clampAtLeast } from '@/shared/lib/number';

export const isProductInCart = (
  cart: Cart | null,
  productId: number,
  options?: CartActionOptions,
) => {
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

    const currentMonths = clampAtLeast(item.rentalMonths ?? 1, 1);
    return currentMonths === clampAtLeast(wantedMonths, 1);
  });
};
