import { Link, useNavigate } from 'react-router-dom';
import { useCallback, useEffect, useState, useMemo } from 'react';

import { useCart } from '@/features/cart/hooks/useCart';
import { useCatalogMenu } from '@/features/catalog/hooks/useCatalogMenu';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { checkoutOrder } from '@/features/orders/api';
import { fetchMyAddresses, type AddressDto } from '@/features/addresses/api';
import { useToast } from '@/shared/components/ui/toast';
import type { CartItem as CartLine } from '@/features/cart/types';

import './CartPage.css';

const formatPrice = (valueInCents: number) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(valueInCents / 100);

export const CartPage = () => {
  useDocumentTitle('Mon panier');

  const {
    cart,
    status,
    error,
    removeItem,
    setItemQuantity,
    clear,
    isProductPending,
    isClearing,
  } = useCart();
  const { categories: catalogCategories } = useCatalogMenu();
  const navigate = useNavigate();
  const { status: authStatus } = useAuth();
  const { show } = useToast();

  const [isCheckout, setIsCheckout] = useState(false);
  const [addresses, setAddresses] = useState<AddressDto[]>([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);

  const isLoading = status === 'loading';
  const hasItems = !!(cart && cart.items.length > 0);

  const shoppingLink = useMemo(
    () =>
      catalogCategories[0]?.slug
        ? `/catalogue/${catalogCategories[0].slug}`
        : '/',
    [catalogCategories]
  );

  // --- Handlers ---
  const handleDecrease = useCallback(
    (item: CartLine) => {
      const isRental = item.product.sellingType === 'rental';
      const rentalReference = isRental ? Math.max(1, item.rentalMonths ?? 1) : undefined;

      if (item.quantity <= 1) {
        void removeItem(
          item.product.id,
          rentalReference ? { currentRentalMonths: rentalReference } : undefined,
        ).catch(() =>
          show('Erreur lors de la suppression du produit.', { variant: 'error' }),
        );
        return;
      }

      void setItemQuantity(
        item.product.id,
        item.quantity - 1,
        rentalReference ? { currentRentalMonths: rentalReference } : undefined,
      ).catch(() =>
        show('Erreur lors de la mise à jour de la quantité.', { variant: 'error' }),
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
        show('Erreur lors de la mise à jour de la quantité.', { variant: 'error' }),
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
        show('Erreur lors de la mise à jour de la durée.', { variant: 'error' }),
      );
    },
    [setItemQuantity, show],
  );

  const handleClear = useCallback(() => {
    if (window.confirm('Voulez-vous vraiment vider votre panier ?')) {
      void clear().catch(() =>
        show('Erreur lors du vidage du panier.', { variant: 'error' })
      );
    }
  }, [clear, show]);

  // --- Adresses ---
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

  // --- Checkout ---
  const handleCheckout = useCallback(() => {
    if (!hasItems) return;
    if (authStatus !== 'authenticated') {
      show('Vous devez être connecté pour valider une commande.', { variant: 'info' });
      navigate('/login', {
        state: { redirectTo: '/panier' },
      });
      return;
    }

    const addressId = selectedAddressId ?? addresses.find((i) => i.isDefault)?.id;
    if (!addressId) {
      show('Veuillez sélectionner une adresse de livraison.', { variant: 'error' });
      return;
    }

    setIsCheckout(true);
    void checkoutOrder(addressId)
      .then((order) =>
        navigate(`/orders/${order.id}`, {
          state: { justConfirmed: true },
        }),
      )
      .catch(() =>
        show('Erreur lors de la validation de la commande.', { variant: 'error' })
      )
      .finally(() => setIsCheckout(false));
  }, [authStatus, hasItems, selectedAddressId, addresses, navigate, show]);

  // --- Render ---
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
                : 'Aucun article pour le moment.'}
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

        {isLoading && <p>Chargement du panier...</p>}
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
                        {formatPrice(item.product.priceCents)} / unité
                      </span>
                      <span className="cart-page__line-total">
                        Sous-total : {formatPrice(item.linePriceCents)}
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
                            show('Erreur lors du retrait du produit.', { variant: 'error' })
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

            {/* --- Récapitulatif --- */}
            <aside className="cart-page__summary" aria-label="Récapitulatif du panier">
              <h2>Récapitulatif</h2>
              <div className="cart-summary-table">
                <div className="cart-summary-row">
                  <span className="cart-summary-label">Articles</span>
                  <span className="cart-summary-value">{cart?.totalQuantity ?? 0}</span>
                </div>
                <br />
                <div className="cart-summary-row">
                <span className="cart-summary-label">Mis à jour le&nbsp;</span>
                  <span className="cart-summary-value">
                    {cart
                      ? new Date(cart.updatedAt).toLocaleDateString('fr-FR', {
                          day: '2-digit',
                          month: 'long',
                          year: 'numeric',
                        })
                      : '-'}
                  </span>
                </div>
                <br />
                <div className="cart-summary-row cart-summary-total">
                <span className="cart-summary-label">Total TTC&nbsp;</span>
                  <span className="cart-summary-value cart-summary-total-value">
                    {formatPrice(cart?.totalPriceCents ?? 0)}
                  </span>
                </div>
              </div>

              {authStatus === 'authenticated' && (
                <>
                  <h2>Lieu de livraison</h2>
                  <div className="cart-summary-address">
                    {addressesLoading ? (
                      <p>Chargement...</p>
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
                        Aucune adresse.{' '}
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
              <p>Votre panier est vide.</p>
              <button
                type="button"
                className="hero__button hero__button--primary"
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
