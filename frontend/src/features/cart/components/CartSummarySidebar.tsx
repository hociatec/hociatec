import type { AddressDto } from '@/features/addresses/api/addressesApi';
import type { Cart } from '@/features/cart/types/cart';
import { formatCartPrice, formatPromotionValue } from '@/features/cart/utils/cartDisplay';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatFrenchDate } from '@/shared/lib/formatters';
import { CartSummaryActions } from './CartPageBaseSections';

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
