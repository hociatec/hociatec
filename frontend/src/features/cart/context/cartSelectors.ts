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
  const wantedStartDate = options?.rentalStartDate ?? options?.currentRentalStartDate;
  const wantedSellingType = options?.currentSellingType ?? options?.sellingType;

  return cart.items.some((item) => {
    if (item.product.id !== productId) {
      return false;
    }

    if (wantedSellingType && item.sellingType !== wantedSellingType) {
      return false;
    }

    if (item.sellingType !== 'rental' || wantedMonths === undefined) {
      return true;
    }

    const currentMonths = clampAtLeast(item.rentalMonths ?? 1, 1);
    if (currentMonths !== clampAtLeast(wantedMonths, 1)) {
      return false;
    }

    if (!wantedStartDate) {
      return true;
    }

    return (item.rentalStartDate ?? null) === wantedStartDate;
  });
};
