import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { useMutation } from '@tanstack/react-query';

import { useAuth } from '@/features/auth/hooks/useAuth';
import { useCart } from '@/features/cart/hooks/useCart';
import type { CartItem as CartLine } from '@/features/cart/types/cart';
import { useCartCheckout } from '@/features/cart/hooks/useCartCheckout';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';

export const useCartPageController = () => {
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
  const [promotionCode, setPromotionCode] = useState('');
  const isLoading = status === 'loading';
  const hasItems = !!(cart && cart.items.length > 0);
  const isPromotionCodeEmpty = promotionCode.trim() === '';
  const checkoutState = useCartCheckout(authStatus === 'authenticated');

  useEffect(() => {
    setPromotionCode(cart?.enteredVoucherCode ?? '');
  }, [cart?.enteredVoucherCode]);

  const applyPromotionMutation = useMutation({
    mutationFn: (code: string) => applyVoucherCode(code),
    onSuccess: () => show('Code promo appliqué au panier.', { variant: 'success' }),
    onError: (reason) =>
      show(getHttpErrorMessage(reason, "Impossible d'appliquer le bon de réduction."), {
        variant: 'error',
      }),
  });
  const clearPromotionMutation = useMutation({
    mutationFn: (token: string | undefined) => clearVoucherCode(token),
    onSuccess: () => show('Code promo retiré du panier.', { variant: 'success' }),
    onError: (reason) =>
      show(getHttpErrorMessage(reason, 'Impossible de supprimer le bon de réduction.'), {
        variant: 'error',
      }),
  });

  const handleDecrease = useCallback(
    (item: CartLine) => {
      const isRental = item.product.sellingType === 'rental';
      const rentalReference = isRental ? Math.max(1, item.rentalMonths ?? 1) : undefined;

      if (item.quantity <= 1) {
        void removeItem(
          item.product.id,
          rentalReference ? { currentRentalMonths: rentalReference } : undefined,
        ).catch(() =>
          show("Nous n'avons pas pu retirer cet article. Réessayez dans quelques secondes.", {
            variant: 'error',
          }),
        );
        return;
      }

      void setItemQuantity(
        item.product.id,
        item.quantity - 1,
        rentalReference ? { currentRentalMonths: rentalReference } : undefined,
      ).catch(() =>
        show("La quantité n'a pas pu être mise à jour. Vérifiez le stock puis réessayez.", {
          variant: 'error',
        }),
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
        show("La quantité n'a pas pu être mise à jour. Vérifiez le stock puis réessayez.", {
          variant: 'error',
        }),
      );
    },
    [setItemQuantity, show],
  );

  const updateRentalMonths = useCallback(
    (item: CartLine, nextValue: number) => {
      if (item.product.sellingType !== 'rental') return;

      const currentMonths = Math.max(1, item.rentalMonths ?? 1);
      const normalized = Math.max(1, Number.isNaN(nextValue) ? 1 : nextValue);
      if (normalized === currentMonths) return;

      void setItemQuantity(item.product.id, item.quantity, {
        currentRentalMonths: currentMonths,
        rentalMonths: normalized,
      }).catch(() =>
        show("La durée de location n'a pas pu être mise à jour. Réessayez avant de valider.", {
          variant: 'error',
        }),
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
        show("Le panier n'a pas pu être vidé. Réessayez dans quelques secondes.", {
          variant: 'error',
        }),
      );
    });
  }, [clear, confirm, show]);

  const handleApplyPromotionCode = useCallback(() => {
    const trimmed = promotionCode.trim();
    if (trimmed === '') {
      show('Saisissez votre code promo avant de l’appliquer.', { variant: 'info' });
      return;
    }

    applyPromotionMutation.mutate(trimmed);
  }, [applyPromotionMutation, promotionCode, show]);

  const handleClearPromotionCode = useCallback(() => {
    clearPromotionMutation.mutate(cart?.token);
  }, [cart?.token, clearPromotionMutation]);

  const handleRemoveItem = useCallback(
    (item: CartLine) => {
      const isRental = item.product.sellingType === 'rental';
      const rentalMonths = Math.max(1, item.rentalMonths ?? 1);
      void removeItem(
        item.product.id,
        isRental ? { currentRentalMonths: rentalMonths } : undefined,
      ).catch(() =>
        show("Nous n'avons pas pu retirer cet article. Réessayez dans quelques secondes.", {
          variant: 'error',
        }),
      );
    },
    [removeItem, show],
  );

  const handleCheckout = useCallback(() => {
    if (!hasItems) return;
    if (authStatus !== 'authenticated') {
      show('Connectez-vous pour finaliser votre commande et retrouver vos informations de livraison.', {
        variant: 'info',
      });
      navigate('/login', { state: { redirectTo: '/panier' } });
      return;
    }

    void checkoutState.checkout(redirectToTrustedUrl)
      .then((order) => {
        if (order) {
          resetAfterCheckout();
          navigate(`/orders/${order.id}`, { state: { justConfirmed: true } });
        }
      })
      .catch((reason: unknown) =>
        show(
          reason instanceof Error
            ? reason.message
            : "La commande n'a pas pu être validée. Vérifiez votre panier puis réessayez.",
          { variant: 'error' },
        ),
      );
  }, [authStatus, checkoutState, hasItems, navigate, resetAfterCheckout, show]);

  return {
    ...checkoutState,
    authStatus,
    cart,
    error,
    handleApplyPromotionCode,
    handleCheckout,
    handleClear,
    handleClearPromotionCode,
    handleDecrease,
    handleIncrease,
    handleRemoveItem,
    hasItems,
    isApplyingPromotionCode: applyPromotionMutation.isPending || clearPromotionMutation.isPending,
    isLoading,
    isProductPending,
    isPromotionCodeEmpty,
    isClearing,
    navigate,
    promotionCode,
    setPromotionCode,
    updateRentalMonths,
  };
};
