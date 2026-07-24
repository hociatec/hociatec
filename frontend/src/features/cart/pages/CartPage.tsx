import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useNavigate } from 'react-router-dom';
import { useCallback, useEffect, useState } from 'react';

import { useCart } from '@/features/cart/hooks/useCart';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { checkoutOrder, type CheckoutRedirectDto, type OrderDto } from '@/features/orders/api';
import { fetchMyAddresses, type AddressDto } from '@/features/addresses/api';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import type { CartItem as CartLine } from '@/features/cart/types';
import { CartItemsList, CartPageHeader, CartSummarySidebar, EmptyCartState } from './CartPageSections';
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
  const [addressesError, setAddressesError] = useState<string | null>(null);
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

  const handleRemoveItem = useCallback(
    (item: CartLine) => {
      const isRental = item.product.sellingType === 'rental';
      const rentalMonths = Math.max(1, item.rentalMonths ?? 1);

      void removeItem(
        item.product.id,
        isRental ? { currentRentalMonths: rentalMonths } : undefined,
      ).catch(() =>
        show("Nous n'avons pas pu retirer cet article. Réessayez dans quelques secondes.", { variant: 'error' }),
      );
    },
    [removeItem, show],
  );

  useEffect(() => {
    if (authStatus !== 'authenticated') {
      setAddresses([]);
      setAddressesError(null);
      setSelectedAddressId(null);
      return;
    }

    setAddressesLoading(true);
    setAddressesError(null);
    void fetchMyAddresses()
      .then((items) => {
        setAddresses(items);
        const d = items.find((i) => i.isDefault) ?? items[0];
        if (d) setSelectedAddressId(d.id);
      })
      .catch((err: unknown) => {
        setAddresses([]);
        setSelectedAddressId(null);
        setAddressesError(getHttpErrorMessage(err, 'Impossible de charger vos adresses de livraison.'));
      })
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
        <CartPageHeader
          hasItems={hasItems}
          isClearing={isClearing}
          onClear={handleClear}
          totalQuantity={cart?.totalQuantity ?? 0}
        />

        {isLoading && <LoadingState>Chargement de votre panier...</LoadingState>}
        {error && <FeedbackMessage>{error}</FeedbackMessage>}

        {hasItems ? (
          <div className="cart-page__content">
            <CartItemsList
              items={cart?.items ?? []}
              isProductPending={isProductPending}
              onDecrease={handleDecrease}
              onIncrease={handleIncrease}
              onRemove={handleRemoveItem}
              onUpdateRentalMonths={updateRentalMonths}
            />

            <CartSummarySidebar
              addresses={addresses}
              addressesError={addressesError}
                addressesLoading={addressesLoading}
                authStatus={authStatus}
              cart={cart}
              isApplyingPromotionCode={isApplyingPromotionCode}
                isCheckout={isCheckout}
              isPromotionCodeEmpty={isPromotionCodeEmpty}
              promotionCode={promotionCode}
              selectedAddressId={selectedAddressId}
              onAddAddress={() => navigate('/profile/addresses')}
              onAddressSelect={setSelectedAddressId}
              onApplyPromotionCode={handleApplyPromotionCode}
                onCheckout={handleCheckout}
              onClearPromotionCode={handleClearPromotionCode}
                onContinueShopping={() => navigate(shoppingLink)}
              onPromotionCodeChange={setPromotionCode}
            />
          </div>
        ) : (
          !isLoading && (
            <EmptyCartState onExplore={() => navigate(shoppingLink)} />
          )
        )}
      </div>
    </SiteLayout>
  );
};
