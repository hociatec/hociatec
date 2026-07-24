import { Link } from 'react-router-dom';

import type { AddressDto } from '@/features/addresses/api';
import type { Cart, CartItem } from '@/features/cart/types';
import { formatCartPrice, formatPromotionValue } from '@/features/cart/utils/cartDisplay';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatFrenchDate } from '@/shared/lib/formatters';

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
    <span>Explorez le catalogue pour ajouter un produit, une location ou préparer votre prochaine commande.</span>
    <button
      type="button"
      className="cart-page__empty-button"
      onClick={onExplore}
    >
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
        isCheckout ||
        (authStatus === 'authenticated' &&
          (addressesLoading || !selectedAddressId))
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
      const rentalMonths = Math.max(1, item.rentalMonths ?? 1);

      return (
        <li key={item.id ?? item.product.id} className="cart-page__item">
          {item.product.imageUrl ? (
            <img
              src={item.product.imageUrl}
              alt={item.product.imageAlt ?? item.product.name}
              className="cart-page__image"
              loading="lazy"
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
                    onChange={(event) => onUpdateRentalMonths(item, Number.parseInt(event.target.value, 10))}
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

interface CartSummarySidebarProps {
  addresses: AddressDto[];
  addressesError: string | null;
  addressesLoading: boolean;
  authStatus: string;
  cart: Cart | null;
  isApplyingPromotionCode: boolean;
  isCheckout: boolean;
  isPromotionCodeEmpty: boolean;
  promotionCode: string;
  selectedAddressId: number | null;
  onAddAddress: () => void;
  onAddressSelect: (id: number) => void;
  onApplyPromotionCode: () => void;
  onCheckout: () => void;
  onClearPromotionCode: () => void;
  onContinueShopping: () => void;
  onPromotionCodeChange: (value: string) => void;
}

export const CartSummarySidebar = ({
  addresses,
  addressesError,
  addressesLoading,
  authStatus,
  cart,
  isApplyingPromotionCode,
  isCheckout,
  isPromotionCodeEmpty,
  promotionCode,
  selectedAddressId,
  onAddAddress,
  onAddressSelect,
  onApplyPromotionCode,
  onCheckout,
  onClearPromotionCode,
  onContinueShopping,
  onPromotionCodeChange,
}: CartSummarySidebarProps) => (
  <aside className="cart-page__summary" aria-label="Récapitulatif du panier">
    <h2>Récapitulatif</h2>
    <div className="cart-summary-table">
      <div className="cart-summary-row">
        <span className="cart-summary-label">Articles</span>
        <span className="cart-summary-value">{cart?.totalQuantity ?? 0}</span>
      </div>
      <div className="cart-summary-row">
        <span className="cart-summary-label">Sous-total</span>
        <span className="cart-summary-value">{formatCartPrice(cart?.subtotalPriceCents ?? 0)}</span>
      </div>
      <div className="cart-summary-row">
        <span className="cart-summary-label">Mis à jour le&nbsp;</span>
        <span className="cart-summary-value">{cart ? formatFrenchDate(cart.updatedAt) : '-'}</span>
      </div>
      {(cart?.discountAmountCents ?? 0) > 0 && (
        <div className="cart-summary-row">
          <span className="cart-summary-label">Remise</span>
          <span className="cart-summary-value cart-summary-value--discount">
            - {formatCartPrice(cart?.discountAmountCents ?? 0)}
          </span>
        </div>
      )}
      <div className="cart-summary-row cart-summary-total">
        <span className="cart-summary-label">Total TTC&nbsp;</span>
        <span className="cart-summary-value cart-summary-total-value">
          {formatCartPrice(cart?.totalPriceCents ?? 0)}
        </span>
      </div>
    </div>

    <div className="cart-promotion">
      <div className="cart-promotion__label">Code promo</div>
      <div className="cart-promotion__form">
        <input
          type="text"
          className="cart-promotion__input"
          value={promotionCode}
          onChange={(event) => onPromotionCodeChange(event.target.value.toUpperCase())}
          placeholder="Ex. BIENVENUE10"
          disabled={isApplyingPromotionCode}
        />
        <button
          type="button"
          className="cart-promotion__button"
          onClick={onApplyPromotionCode}
          disabled={isApplyingPromotionCode || isPromotionCodeEmpty}
        >
          {isApplyingPromotionCode ? 'Validation...' : 'Appliquer'}
        </button>
        {cart?.enteredVoucherCode ? (
          <button
            type="button"
            className="cart-promotion__button cart-promotion__button--ghost"
            onClick={onClearPromotionCode}
            disabled={isApplyingPromotionCode}
          >
            Supprimer
          </button>
        ) : null}
      </div>
      {cart?.voucherCodeStatus === 'invalid' ? (
        <div className="cart-promotion__message cart-promotion__message--error">Ce bon de réduction est invalide.</div>
      ) : null}
      {cart?.voucherCodeStatus === 'ineligible' ? (
        <div className="cart-promotion__message cart-promotion__message--warning">
          Ce bon de réduction existe mais n’est pas éligible pour ce panier.
        </div>
      ) : null}
    </div>

    {cart?.appliedVoucher ? (
      <div className="cart-promotion-card cart-promotion-card--success">
        <div className="cart-promotion-card__label">Code promo appliqué</div>
        <div className="cart-promotion-card__title">{cart.appliedVoucher.name}</div>
        <div className="cart-promotion-card__text">
          Remise {formatPromotionValue(cart.appliedVoucher.discountType, cart.appliedVoucher.discountValue)}.
        </div>
        {cart.appliedVoucher.code ? (
          <div className="cart-promotion-card__code">Code: {cart.appliedVoucher.code}</div>
        ) : null}
      </div>
    ) : null}

    {cart && cart.eligiblePromotions.length > 1 ? (
      <div className="cart-promotion-card">
        <div className="cart-promotion-card__label">Promotions éligibles</div>
        <div className="cart-promotion-card__list">
          {cart.eligiblePromotions
            .filter((promotion) => promotion.id !== cart.appliedPromotion?.id)
            .map((promotion) => (
              <div key={promotion.id} className="cart-promotion-card__item">
                <div className="cart-promotion-card__item-title">{promotion.name}</div>
                <div className="cart-promotion-card__text">
                  {formatPromotionValue(promotion.discountType, promotion.discountValue)} potentiels, soit{' '}
                  {formatCartPrice(promotion.discountAmountCents)}.
                </div>
              </div>
            ))}
        </div>
      </div>
    ) : null}

    {authStatus === 'authenticated' && (
      <>
        <h2>Lieu de livraison</h2>
        <div className="cart-summary-address">
          {addressesLoading ? (
            <p role="status">Chargement de vos adresses...</p>
          ) : addressesError ? (
            <FeedbackMessage>{addressesError}</FeedbackMessage>
          ) : addresses.length > 0 ? (
            <div role="radiogroup" aria-label="Choisir une adresse de livraison">
              {addresses.map((address) => (
                <label key={address.id} className="cart-summary-address-item">
                  <input
                    type="radio"
                    name="shippingAddress"
                    value={address.id}
                    checked={selectedAddressId === address.id}
                    onChange={() => onAddressSelect(address.id)}
                  />
                  {`${address.name} - ${address.address}, ${address.postalCode} ${address.city}`}
                  {address.isDefault ? ' (par défaut)' : ''}
                </label>
              ))}
            </div>
          ) : (
            <span>
              Aucune adresse de livraison enregistrée.{' '}
              <button type="button" className="link" onClick={onAddAddress}>
                Ajouter
              </button>
            </span>
          )}
        </div>
      </>
    )}

    <CartSummaryActions
      addressesLoading={addressesLoading}
      authStatus={authStatus}
      isCheckout={isCheckout}
      onCheckout={onCheckout}
      onContinueShopping={onContinueShopping}
      selectedAddressId={selectedAddressId}
    />
  </aside>
);
