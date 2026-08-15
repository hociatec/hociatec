import { Link } from 'react-router';

import type { CartItem } from '@/features/cart/types/cart';
import { formatCartPrice } from '@/features/cart/utils/cartDisplay';
import { formatDateInputForDisplay } from '@/shared/lib/formatters';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { clampAtLeast } from '@/shared/lib/number';

interface CartPageHeaderProps {
  hasItems: boolean;
  isClearing: boolean;
  onClear: () => void;
  totalQuantity: number;
}

export const CartPageHeader = ({
  hasItems,
  isClearing,
  onClear,
  totalQuantity,
}: CartPageHeaderProps) => (
  <header className="cart-page__header">
    <div className="cart-page__header-info">
      <h1>Mon panier</h1>
      <p>
        {hasItems
          ? `${totalQuantity} article${totalQuantity > 1 ? 's' : ''}`
          : 'Votre panier est prêt à accueillir vos prochains produits.'}
      </p>
    </div>
    {hasItems && (
      <button
        type="button"
        className="catalog-cart-button catalog-cart-button--remove cart-page__clear"
        onClick={onClear}
        disabled={isClearing}
      >
        {isClearing ? 'Vidage...' : 'Vider le panier'}
      </button>
    )}
  </header>
);

interface EmptyCartStateProps {
  onExplore: () => void;
}

export const EmptyCartState = ({ onExplore }: EmptyCartStateProps) => (
  <div className="cart-page__empty">
    <p>Votre panier est vide pour le moment.</p>
    <span>
      Explorez le catalogue pour ajouter un produit, une location ou préparer votre prochaine
      commande.
    </span>
    <button type="button" className="cart-page__empty-button" onClick={onExplore}>
      Explorer nos solutions
    </button>
  </div>
);

interface CartSummaryActionsProps {
  addressesLoading: boolean;
  authStatus: string;
  isCheckout: boolean;
  onCheckout: () => void;
  onContinueShopping: () => void;
  selectedAddressId: number | null;
}

export const CartSummaryActions = ({
  addressesLoading,
  authStatus,
  isCheckout,
  onCheckout,
  onContinueShopping,
  selectedAddressId,
}: CartSummaryActionsProps) => (
  <div className="cart-summary-actions">
    <button
      type="button"
      className="hero__button hero__button--primary"
      onClick={onContinueShopping}
    >
      Continuer mes achats
    </button>

    <button
      type="button"
      className="hero__button hero__button--secondary"
      onClick={onCheckout}
      disabled={
        isCheckout || (authStatus === 'authenticated' && (addressesLoading || !selectedAddressId))
      }
    >
      {isCheckout ? 'Validation...' : 'Valider ma commande'}
    </button>
  </div>
);

interface CartItemsListProps {
  items: CartItem[];
  isProductPending: (productId: number) => boolean;
  onDecrease: (item: CartItem) => void;
  onIncrease: (item: CartItem) => void;
  onRemove: (item: CartItem) => void;
  onUpdateRentalMonths: (item: CartItem, nextValue: number) => void;
}

export const CartItemsList = ({
  items,
  isProductPending,
  onDecrease,
  onIncrease,
  onRemove,
  onUpdateRentalMonths,
}: CartItemsListProps) => (
  <ul className="cart-page__list">
    {items.map((item) => {
      const pending = isProductPending(item.product.id);
      const isRental = item.product.sellingType === 'rental';
      const rentalMonths = clampAtLeast(item.rentalMonths ?? 1, 1);

      return (
        <li key={item.id ?? item.product.id} className="cart-page__item">
          {item.product.imageUrl ? (
            <img
              src={item.product.imageUrl}
              alt={item.product.imageAlt ?? item.product.name}
              className="cart-page__image"
              width={96}
              height={96}
              loading="lazy"
              decoding="async"
            />
          ) : (
            <div className="cart-page__placeholder" aria-hidden="true">
              {item.product.name.charAt(0).toUpperCase()}
            </div>
          )}

          <div className="cart-page__details">
            <Link to={`/catalogue/produits/${item.product.slug}`} className="cart-page__title">
              {item.product.name}
            </Link>
            <span className="cart-page__meta">SKU {item.product.sku}</span>
            <span className="cart-page__price">
              {formatCartPrice(item.product.priceCents)} / unité
            </span>
            <span className="cart-page__line-total">
              Sous-total : {formatCartPrice(item.linePriceCents)}
            </span>
            {isRental && item.rentalStartDate ? (
              <span className="cart-page__meta">
                Du {formatDateInputForDisplay(item.rentalStartDate)} au{' '}
                {formatDateInputForDisplay(item.rentalEndDate ?? item.rentalStartDate)}
              </span>
            ) : null}
          </div>

          <div className="cart-page__actions">
            <div
              className="cart-page__quantity-controls"
              role="group"
              aria-label={`Quantité pour ${item.product.name}`}
            >
              <button
                type="button"
                className="cart-page__quantity-button"
                onClick={() => onDecrease(item)}
                disabled={pending}
              >
                -
              </button>
              <span className="cart-page__quantity-value">{item.quantity}</span>
              <button
                type="button"
                className="cart-page__quantity-button"
                onClick={() => onIncrease(item)}
                disabled={pending}
              >
                +
              </button>
            </div>

            {isRental && (
              <div className="cart-page__rental">
                <span className="cart-page__rental-label">Durée de location (mois)</span>
                <div
                  className="cart-page__rental-controls"
                  role="group"
                  aria-label={`Durée pour ${item.product.name}`}
                >
                  <button
                    type="button"
                    className="cart-page__rental-button"
                    onClick={() => onUpdateRentalMonths(item, rentalMonths - 1)}
                    disabled={pending || rentalMonths <= 1}
                  >
                    -
                  </button>
                  <input
                    type="number"
                    min={1}
                    className="cart-page__rental-input"
                    value={rentalMonths}
                    onChange={(event) =>
                      onUpdateRentalMonths(item, parseNullablePositiveInteger(event.target.value) ?? 1)
                    }
                    disabled={pending}
                  />
                  <button
                    type="button"
                    className="cart-page__rental-button"
                    onClick={() => onUpdateRentalMonths(item, rentalMonths + 1)}
                    disabled={pending}
                  >
                    +
                  </button>
                </div>
              </div>
            )}

            <button
              type="button"
              className="catalog-cart-button catalog-cart-button--remove cart-page__remove"
              onClick={() => onRemove(item)}
              disabled={pending}
            >
              {pending ? 'Mise à jour...' : 'Retirer'}
            </button>
          </div>
        </li>
      );
    })}
  </ul>
);
