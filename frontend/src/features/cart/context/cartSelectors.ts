import type { Cart, CartActionOptions } from '@/features/cart/types/cart';

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

    const currentMonths = Math.max(1, item.rentalMonths ?? 1);
    return currentMonths === Math.max(1, wantedMonths);
  });
};
