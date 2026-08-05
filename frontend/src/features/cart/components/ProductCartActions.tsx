import { useEffect, useMemo, useState, type MouseEvent } from 'react';
import { useMutation } from '@tanstack/react-query';

import type { CatalogProduct } from '@/features/catalog/publicApi';

import { useCart } from '../hooks/useCart';
import { useToast } from '@/shared/components/ui/toast';

interface ProductCartActionsProps {
  product: CatalogProduct;
  variant?: 'card' | 'detail';
}

export const ProductCartActions = ({ product, variant = 'card' }: ProductCartActionsProps) => {
  const { cart, addItem, removeItem, isProductInCart, isProductPending } = useCart();
  const { show } = useToast();

  const isPending = isProductPending(product.id);
  const isRentalProduct = product.sellingType === 'rental';
  const [rentalMonths, setRentalMonths] = useState<number>(() => (isRentalProduct ? 1 : 0));
  const normalizedRentalMonths = isRentalProduct ? Math.max(1, rentalMonths || 1) : undefined;

  const matchingItem = useMemo(() => {
    if (!cart) {
      return null;
    }

    if (!isRentalProduct) {
      return cart.items.find((item) => item.product.id === product.id) ?? null;
    }

    return (
      cart.items.find(
        (item) =>
          item.product.id === product.id &&
          Math.max(1, item.rentalMonths ?? 1) === Math.max(1, normalizedRentalMonths ?? 1),
      ) ?? null
    );
  }, [cart, product.id, isRentalProduct, normalizedRentalMonths]);

  const hasProductInCart = isProductInCart(
    product.id,
    isRentalProduct && normalizedRentalMonths ? { rentalMonths: normalizedRentalMonths } : undefined,
  );
  const isInCart = hasProductInCart;
  const currentQuantity = matchingItem?.quantity ?? 0;

  const containerClassName = [
    'catalog-cart-actions',
    variant === 'detail' ? 'catalog-cart-actions--detail' : '',
  ]
    .filter(Boolean)
    .join(' ');

  const buttonClassName = [
    'catalog-cart-button',
    isInCart ? 'catalog-cart-button--remove' : '',
    variant === 'detail' ? 'catalog-cart-button--lg' : '',
  ]
    .filter(Boolean)
    .join(' ');

  useEffect(() => {
    if (!isRentalProduct) {
      return;
    }

    const stillInCart = cart?.items.some((item) => item.product.id === product.id) ?? false;
    if (!stillInCart) {
      setRentalMonths(1);
    }
  }, [cart, isRentalProduct, product.id]);

  const removeMutation = useMutation({
    mutationFn: (rentalMonthsValue: number | undefined) =>
      removeItem(
        product.id,
        isRentalProduct && rentalMonthsValue ? { rentalMonths: rentalMonthsValue } : undefined,
      ),
    onSuccess: () => show(`Produit retiré du panier`, { variant: 'info', persistent: true }),
    onError: () => show("Nous n'avons pas pu retirer cet article du panier.", { variant: 'error' }),
  });
  const addMutation = useMutation({
    mutationFn: (rentalMonthsValue: number | undefined) =>
      addItem(
        product.id,
        1,
        isRentalProduct && rentalMonthsValue ? { rentalMonths: rentalMonthsValue } : undefined,
      ),
    onSuccess: () => show(`Produit ajouté au panier`, { variant: 'success', persistent: true }),
    onError: () => show("Nous n'avons pas pu ajouter cet article au panier.", { variant: 'error' }),
  });

  const handleClick = (event: MouseEvent<HTMLButtonElement>) => {
    event.stopPropagation();
    event.preventDefault();

    const effectiveRentalMonths = isRentalProduct
      ? Math.max(1, normalizedRentalMonths ?? 1)
      : undefined;

    if (isInCart) {
      removeMutation.mutate(effectiveRentalMonths);
      return;
    }

    addMutation.mutate(effectiveRentalMonths);
  };

  return (
    <div className={containerClassName}>
      {isInCart && (
        <span className="catalog-cart-quantity" aria-live="polite">
          {isRentalProduct
            ? `Dans le panier (${Math.max(1, normalizedRentalMonths ?? 1)} mois) : ${currentQuantity}`
            : `Dans le panier : ${currentQuantity}`}
        </span>
      )}
      {isRentalProduct && (
        <label className="catalog-cart-rental">
          Durée (mois)
          <input
            type="number"
            min={1}
            value={rentalMonths}
            onChange={(event) => {
              const value = Number.parseInt(event.target.value, 10);
              if (Number.isNaN(value) || value < 1) {
                setRentalMonths(1);
              } else {
                setRentalMonths(value);
              }
            }}
          />
        </label>
      )}
      <button type="button" className={buttonClassName} onClick={handleClick} disabled={isPending}>
        {isPending
          ? isInCart
            ? 'Retrait...'
            : 'Ajout...'
          : isInCart
            ? 'Retirer du panier'
            : 'Ajouter au panier'}
      </button>
    </div>
  );
};
