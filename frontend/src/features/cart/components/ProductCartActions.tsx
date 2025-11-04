import type { MouseEvent } from 'react';

import type { CatalogProduct } from '@/features/catalog/api';

import { useCart } from '../hooks/useCart';
import { useToast } from '@/shared/components/ui/toast';

interface ProductCartActionsProps {
  product: CatalogProduct;
  variant?: 'card' | 'detail';
}

export const ProductCartActions = ({
  product,
  variant = 'card',
}: ProductCartActionsProps) => {
  const { cart, addItem, removeItem, isProductInCart, isProductPending } = useCart();
  const { show } = useToast();

  const isInCart = isProductInCart(product.id);
  const isPending = isProductPending(product.id);
  const currentQuantity =
    cart?.items.find((item) => item.product.id === product.id)?.quantity ?? 0;

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

  const handleClick = (event: MouseEvent<HTMLButtonElement>) => {
    event.stopPropagation();
    event.preventDefault();

    if (isInCart) {
      void removeItem(product.id)
        .then(() => show(`Produit retiré du panier`, { variant: 'info', persistent: true }))
        .catch(() => show(`Impossible de retirer le produit.`, { variant: 'error' }));
      return;
    }

    void addItem(product.id, 1)
      .then(() => show(`Produit ajouté au panier`, { variant: 'success', persistent: true }))
      .catch(() => show(`Impossible d'ajouter le produit.`, { variant: 'error' }));
  };

  return (
    <div className={containerClassName}>
      {isInCart && (
        <span className="catalog-cart-quantity" aria-live="polite">
          Dans le panier : {currentQuantity}
        </span>
      )}
      <button
        type="button"
        className={buttonClassName}
        onClick={handleClick}
        disabled={isPending}
      >
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
