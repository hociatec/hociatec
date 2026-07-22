import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { Link, useNavigate } from 'react-router-dom';
import { useCallback, useEffect, useState } from 'react';

import { useCart } from '@/features/cart/hooks/useCart';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { checkoutOrder, type CheckoutRedirectDto, type OrderDto } from '@/features/orders/api';
import { fetchMyAddresses, type AddressDto } from '@/features/addresses/api';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import type { CartItem as CartLine } from '@/features/cart/types';
import { formatCartPrice, formatPromotionValue } from '@/features/cart/utils/cartDisplay';
import { formatFrenchDate } from '@/shared/lib/formatters';

import './CartPage.css';

export const CartPage = () => {
  useDocumentTitle('Mon panier');

  const {
    cart,
    status,
    error,
    removeItem,
    setItemQuantity,
    clear,
    applyVoucherCode,
    clearVoucherCode,
    resetAfterCheckout,
    isProductPending,
    isClearing,
  } = useCart();
  const navigate = useNavigate();
  const { status: authStatus } = useAuth();
  const { show } = useToast();
  const confirm = useConfirm();

  const [isCheckout, setIsCheckout] = useState(false);
  const [addresses, setAddresses] = useState<AddressDto[]>([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [promotionCode, setPromotionCode] = useState('');
  const [isApplyingPromotionCode, setIsApplyingPromotionCode] = useState(false);

  const isLoading = status === 'loading';
  const hasItems = !!(cart && cart.items.length > 0);
  const isPromotionCodeEmpty = promotionCode.trim() === '';

  useEffect(() => {
    setPromotionCode(cart?.enteredVoucherCode ?? '');
  }, [cart?.enteredVoucherCode]);

  const shoppingLink = '/catalogue/recherche';


  const handleDecrease = useCallback(
    (item: CartLine) => {
      const isRental = item.product.sellingType === 'rental';
      const rentalReference = isRental ? Math.max(1, item.rentalMonths ?? 1) : undefined;

      if (item.quantity <= 1) {
        void removeItem(
          item.product.id,
          rentalReference ? { currentRentalMonths: rentalReference } : undefined,
        ).catch(() =>
          show("Nous n'avons pas pu retirer cet article. Réessayez dans quelques secondes.", { variant: 'error' }),
        );
        return;
      }

      void setItemQuantity(
        item.product.id,
        item.quantity - 1,
        rentalReference ? { currentRentalMonths: rentalReference } : undefined,
      ).catch(() =>
        show("La quantité n'a pas pu être mise à jour. Vérifiez le stock puis réessayez.", { variant: 'error' }),
      );
    },
    [removeItem, setItemQuantity, show],
  );

  const handleIncrease = useCallback(
    (item: CartLine) => {
      const isRental = item.product.sellingType === 'rental';
      const rentalReference = isRental ? Math.max(1, item.rentalMonths ?? 1) : undefined;

      void setItemQuantity(
        item.product.id,
        item.quantity + 1,
        rentalReference ? { currentRentalMonths: rentalReference } : undefined,
      ).catch(() =>
        show("La quantité n'a pas pu être mise à jour. Vérifiez le stock puis réessayez.", { variant: 'error' }),
      );
    },
    [setItemQuantity, show],
  );

  const updateRentalMonths = useCallback(
    (item: CartLine, nextValue: number) => {
      if (item.product.sellingType !== 'rental') {
        return;
      }
      const currentMonths = Math.max(1, item.rentalMonths ?? 1);
      const normalized = Math.max(1, Number.isNaN(nextValue) ? 1 : nextValue);

      if (normalized === currentMonths) {
        return;
      }

      void setItemQuantity(item.product.id, item.quantity, {
        currentRentalMonths: currentMonths,
        rentalMonths: normalized,
      }).catch(() =>
        show("La durée de location n'a pas pu être mise à jour. Réessayez avant de valider.", { variant: 'error' }),
      );
    },
    [setItemQuantity, show],
  );

  const handleClear = useCallback(() => {
    void confirm({
      title: 'Vider le panier',
      description: 'Tous les articles et codes promo associés seront retirés.',
      confirmLabel: 'Vider',
      cancelLabel: 'Conserver',
    }).then((confirmed) => {
      if (!confirmed) return;

      void clear().catch(() =>
        show("Le panier n'a pas pu être vidé. Réessayez dans quelques secondes.", { variant: 'error' })
      );
    });
  }, [clear, confirm, show]);

  const handleApplyPromotionCode = useCallback(() => {
    const trimmed = promotionCode.trim();
    if (trimmed === '') {
      show('Saisissez votre code promo avant de l’appliquer.', { variant: 'info' });
      return;
    }

    setIsApplyingPromotionCode(true);
    void applyVoucherCode(trimmed)
      .then(() => show('Code promo appliqué au panier.', { variant: 'success' }))
      .catch((err) => show(getHttpErrorMessage(err, "Impossible d'appliquer le bon de réduction."), { variant: 'error' }))
      .finally(() => setIsApplyingPromotionCode(false));
  }, [applyVoucherCode, promotionCode, show]);

  const handleClearPromotionCode = useCallback(() => {
    setIsApplyingPromotionCode(true);
    void clearVoucherCode(cart?.token)
      .then(() => show('Code promo retiré du panier.', { variant: 'success' }))
      .catch((err) => show(getHttpErrorMessage(err, 'Impossible de supprimer le bon de réduction.'), { variant: 'error' }))
      .finally(() => setIsApplyingPromotionCode(false));
  }, [cart?.token, clearVoucherCode, show]);

  useEffect(() => {
    if (authStatus !== 'authenticated') {
      setAddresses([]);
      setSelectedAddressId(null);
      return;
    }

    setAddressesLoading(true);
    void fetchMyAddresses()
      .then((items) => {
        setAddresses(items);
        const d = items.find((i) => i.isDefault) ?? items[0];
        if (d) setSelectedAddressId(d.id);
      })
      .catch(() => setAddresses([]))
      .finally(() => setAddressesLoading(false));
  }, [authStatus]);

  const handleCheckout = useCallback(() => {
    if (!hasItems) return;
    if (authStatus !== 'authenticated') {
      show('Connectez-vous pour finaliser votre commande et retrouver vos informations de livraison.', { variant: 'info' });
      navigate('/login', {
        state: { redirectTo: '/panier' },
      });
      return;
    }

    const addressId = selectedAddressId ?? addresses.find((i) => i.isDefault)?.id;
    if (!addressId) {
      show('Choisissez une adresse de livraison avant de passer au paiement.', { variant: 'error' });
      return;
    }

    setIsCheckout(true);
    void checkoutOrder(addressId)
      .then((result) => {
        if ((result as CheckoutRedirectDto).mode === 'redirect') {
          window.location.assign((result as CheckoutRedirectDto).checkoutUrl);
          return;
        }

        const order = result as OrderDto;
        resetAfterCheckout();
        navigate(`/orders/${order.id}`, {
          state: { justConfirmed: true },
        });
      })
      .catch((err: unknown) =>
        show(err instanceof Error ? err.message : "La commande n'a pas pu être validée. Vérifiez votre panier puis réessayez.", { variant: 'error' })
      )
      .finally(() => setIsCheckout(false));
  }, [authStatus, hasItems, selectedAddressId, addresses, navigate, resetAfterCheckout, show]);

  return (
    <SiteLayout headerVariant="light">
      <div className="cart-page">
        <header className="cart-page__header">
          <div className="cart-page__header-info">
            <h1>Mon panier</h1>
            <p>
              {hasItems
                ? `${cart?.totalQuantity ?? 0} article${
                    (cart?.totalQuantity ?? 0) > 1 ? 's' : ''
                  }`
                : 'Votre panier est prêt à accueillir vos prochains produits.'}
            </p>
          </div>
          {hasItems && (
            <button
              type="button"
              className="catalog-cart-button catalog-cart-button--remove cart-page__clear"
              onClick={handleClear}
              disabled={isClearing}
            >
              {isClearing ? 'Vidage...' : 'Vider le panier'}
            </button>
          )}
        </header>

        {isLoading && <p aria-hidden="true">Chargement de votre panier...</p>}
        {error && <div className="cart-page__alert">{error}</div>}

        {hasItems ? (
          <div className="cart-page__content">
            <ul className="cart-page__list">
              {cart?.items.map((item) => {
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
                      <Link
                        to={`/catalogue/produits/${item.product.slug}`}
                        className="cart-page__title"
                      >
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
                          onClick={() => handleDecrease(item)}
                          disabled={pending}
                        >
                          −
                        </button>
                        <span className="cart-page__quantity-value">{item.quantity}</span>
                        <button
                          type="button"
                          className="cart-page__quantity-button"
                          onClick={() => handleIncrease(item)}
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
                              onClick={() => updateRentalMonths(item, rentalMonths - 1)}
                              disabled={pending || rentalMonths <= 1}
                            >
                              −
                            </button>
                            <input
                              type="number"
                              min={1}
                              className="cart-page__rental-input"
                              value={rentalMonths}
                              onChange={(event) =>
                                updateRentalMonths(item, Number.parseInt(event.target.value, 10))
                              }
                              disabled={pending}
                            />
                            <button
                              type="button"
                              className="cart-page__rental-button"
                              onClick={() => updateRentalMonths(item, rentalMonths + 1)}
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
                        onClick={() =>
                          void removeItem(
                            item.product.id,
                            isRental ? { currentRentalMonths: rentalMonths } : undefined,
                          ).catch(() =>
                            show("Nous n'avons pas pu retirer cet article. Réessayez dans quelques secondes.", { variant: 'error' })
                          )
                        }
                        disabled={pending}
                      >
                        {pending ? 'Mise à jour...' : 'Retirer'}
                      </button>
                    </div>
                  </li>
                );
              })}
            </ul>

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
                  <span className="cart-summary-value">
                    {cart
                      ? formatFrenchDate(cart.updatedAt)
                      : '-'}
                  </span>
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
                    onChange={(event) => setPromotionCode(event.target.value.toUpperCase())}
                    placeholder="Ex. BIENVENUE10"
                    disabled={isApplyingPromotionCode}
                  />
                  <button type="button" className="cart-promotion__button" onClick={handleApplyPromotionCode} disabled={isApplyingPromotionCode || isPromotionCodeEmpty}>
                    {isApplyingPromotionCode ? 'Validation...' : 'Appliquer'}
                  </button>
                  {cart?.enteredVoucherCode ? (
                    <button type="button" className="cart-promotion__button cart-promotion__button--ghost" onClick={handleClearPromotionCode} disabled={isApplyingPromotionCode}>
                      Supprimer
                    </button>
                  ) : null}
                </div>
                {cart?.voucherCodeStatus === 'invalid' ? (
                  <div className="cart-promotion__message cart-promotion__message--error">Ce bon de réduction est invalide.</div>
                ) : null}
                {cart?.voucherCodeStatus === 'ineligible' ? (
                  <div className="cart-promotion__message cart-promotion__message--warning">Ce bon de réduction existe mais n’est pas éligible pour ce panier.</div>
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
                    <div className="cart-promotion-card__code">
                      Code: {cart.appliedVoucher.code}
                    </div>
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
                            {formatPromotionValue(promotion.discountType, promotion.discountValue)} potentiels, soit {formatCartPrice(promotion.discountAmountCents)}.
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
                      <p aria-hidden="true">Chargement de vos adresses...</p>
                    ) : addresses.length > 0 ? (
                      <div role="radiogroup" aria-label="Choisir une adresse de livraison">
                        {addresses.map((a) => (
                          <label key={a.id} className="cart-summary-address-item">
                            <input
                              type="radio"
                              name="shippingAddress"
                              value={a.id}
                              checked={selectedAddressId === a.id}
                              onChange={() => setSelectedAddressId(a.id)}
                            />
                            {`${a.name} - ${a.address}, ${a.postalCode} ${a.city}`}
                            {a.isDefault ? ' (par défaut)' : ''}
                          </label>
                        ))}
                      </div>
                    ) : (
                      <span>
                        Aucune adresse de livraison enregistrée.{' '}
                        <button
                          className="link"
                          onClick={() => navigate('/profile/addresses')}
                        >
                          Ajouter
                        </button>
                      </span>
                    )}
                  </div>
                </>
              )}

              <div className="cart-summary-actions">
                <button
                  type="button"
                  className="hero__button hero__button--primary"
                  onClick={() => navigate(shoppingLink)}
                >
                  Continuer mes achats
                </button>

                <button
                  type="button"
                  className="hero__button hero__button--secondary"
                  onClick={handleCheckout}
                  disabled={
                    isCheckout ||
                    (authStatus === 'authenticated' &&
                      (addressesLoading || !selectedAddressId))
                  }
                >
                  {isCheckout ? 'Validation...' : 'Valider ma commande'}
                </button>
              </div>
            </aside>
          </div>
        ) : (
          !isLoading && (
            <div className="cart-page__empty">
              <p>Votre panier est vide pour le moment.</p>
              <span>Explorez le catalogue pour ajouter un produit, une location ou préparer votre prochaine commande.</span>
              <button
                type="button"
                className="cart-page__empty-button"
                onClick={() => navigate(shoppingLink)}
              >
                Explorer nos solutions
              </button>
            </div>
          )
        )}
      </div>
    </SiteLayout>
  );
};
