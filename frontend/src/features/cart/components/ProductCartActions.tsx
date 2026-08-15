import { useEffect, useMemo, useState, type MouseEvent } from 'react';
import { useMutation } from '@tanstack/react-query';

import type { CatalogProduct } from '@/features/catalog/publicApi';
import { useCart } from '../hooks/useCart';
import { useToast } from '@/shared/components/ui/toast';
import { clampAtLeast } from '@/shared/lib/number';
import { formatApiDateForDateInput, formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';
import { Dialog, DialogBackdrop, DialogDescription, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';

interface ProductCartActionsProps {
  product: CatalogProduct;
  variant?: 'card' | 'detail';
}

const todayDateInput = () => formatApiDateForDateInput(new Date());

const computeRentalEndDate = (startDate: string, rentalMonths: number) => {
  const start = new Date(`${startDate}T00:00:00`);
  if (Number.isNaN(start.getTime())) {
    return startDate;
  }

  start.setMonth(start.getMonth() + Math.max(1, rentalMonths));
  start.setDate(start.getDate() - 1);

  return formatApiDateForDateInput(start);
};

export const ProductCartActions = ({ product, variant = 'card' }: ProductCartActionsProps) => {
  const { cart, addItem, removeItem, isProductInCart, isProductPending } = useCart();
  const { show } = useToast();

  const isPending = isProductPending(product.id);
  const isRentalProduct = product.sellingType === 'rental';
  const [rentalMonths, setRentalMonths] = useState<number>(() => (isRentalProduct ? 1 : 0));
  const [rentalStartDate, setRentalStartDate] = useState<string>(todayDateInput);
  const [modalOpen, setModalOpen] = useState(false);
  const normalizedRentalMonths = isRentalProduct ? clampAtLeast(rentalMonths || 1, 1) : undefined;
  const normalizedRentalStartDate = isRentalProduct ? (rentalStartDate || todayDateInput()) : undefined;
  const computedRentalEndDate = useMemo(
    () =>
      isRentalProduct && normalizedRentalStartDate
        ? computeRentalEndDate(normalizedRentalStartDate, normalizedRentalMonths ?? 1)
        : null,
    [isRentalProduct, normalizedRentalMonths, normalizedRentalStartDate],
  );

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
          clampAtLeast(item.rentalMonths ?? 1, 1) === clampAtLeast(normalizedRentalMonths ?? 1, 1) &&
          (item.rentalStartDate ?? null) === (normalizedRentalStartDate ?? null),
      ) ?? null
    );
  }, [cart, isRentalProduct, normalizedRentalMonths, normalizedRentalStartDate, product.id]);

  const hasProductInCart = isProductInCart(
    product.id,
    isRentalProduct && normalizedRentalMonths && normalizedRentalStartDate
      ? { rentalMonths: normalizedRentalMonths, rentalStartDate: normalizedRentalStartDate }
      : undefined,
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
      setRentalStartDate(todayDateInput());
    }
  }, [cart, isRentalProduct, product.id]);

  const removeMutation = useMutation({
    mutationFn: () =>
      removeItem(
        product.id,
        isRentalProduct && normalizedRentalMonths && normalizedRentalStartDate
          ? { rentalMonths: normalizedRentalMonths, rentalStartDate: normalizedRentalStartDate }
          : undefined,
      ),
    onSuccess: () => show('Produit retiré du panier', { variant: 'info', persistent: true }),
    onError: () => show("Nous n'avons pas pu retirer cet article du panier.", { variant: 'error' }),
  });
  const addMutation = useMutation({
    mutationFn: () =>
      addItem(
        product.id,
        1,
        isRentalProduct && normalizedRentalMonths && normalizedRentalStartDate
          ? { rentalMonths: normalizedRentalMonths, rentalStartDate: normalizedRentalStartDate }
          : undefined,
      ),
    onSuccess: () => show('Produit ajouté au panier', { variant: 'success', persistent: true }),
    onError: () => show("Nous n'avons pas pu ajouter cet article au panier.", { variant: 'error' }),
  });

  const handleClick = (event: MouseEvent<HTMLButtonElement>) => {
    event.stopPropagation();
    event.preventDefault();

    if (isRentalProduct && !isInCart) {
      setModalOpen(true);
      return;
    }

    if (isInCart) {
      removeMutation.mutate();
      return;
    }

    addMutation.mutate();
  };

  const confirmRentalSelection = () => {
    setModalOpen(false);
    addMutation.mutate();
  };

  return (
    <>
      <div className={containerClassName}>
        {isInCart && (
          <span className="catalog-cart-quantity" aria-live="polite">
            {isRentalProduct
              ? `Dans le panier (${clampAtLeast(normalizedRentalMonths ?? 1, 1)} mois, dès le ${formatDateInputForDisplay(normalizedRentalStartDate)}) : ${currentQuantity}`
              : `Dans le panier : ${currentQuantity}`}
          </span>
        )}
        <button type="button" className={buttonClassName} onClick={handleClick} disabled={isPending}>
          {isPending
            ? isInCart
              ? 'Retrait...'
              : 'Ajout...'
            : isInCart
              ? 'Retirer du panier'
              : isRentalProduct
                ? 'Configurer la location'
                : 'Ajouter au panier'}
        </button>
      </div>

      <Dialog open={modalOpen} onClose={setModalOpen} className="relative z-50">
        <DialogBackdrop className="fixed inset-0 bg-brand-950/60" />
        <div className="fixed inset-0 overflow-y-auto px-4 py-6">
          <div className="flex min-h-full items-center justify-center">
            <DialogPanel className="w-full max-w-lg rounded-3xl border border-brand-100 bg-white p-6 shadow-2xl">
              <DialogTitle className="text-xl font-semibold text-brand-950">
                Configurer la location
              </DialogTitle>
              <DialogDescription className="mt-2 text-sm text-stone-600">
                Choisissez la durée et le début de location pour {product.name}.
              </DialogDescription>

              <div className="mt-6 grid gap-4">
                <label className="grid gap-2 text-sm font-medium text-stone-800">
                  Début de location
                  <input
                    type="date"
                    min={todayDateInput()}
                    value={normalizedRentalStartDate}
                    onChange={(event) => setRentalStartDate(event.target.value || todayDateInput())}
                    className="rounded-2xl border border-brand-200 px-4 py-3"
                  />
                </label>

                <label className="grid gap-2 text-sm font-medium text-stone-800">
                  Durée (mois)
                  <input
                    type="number"
                    min={1}
                    value={normalizedRentalMonths ?? 1}
                    onChange={(event) => setRentalMonths(clampAtLeast(Number(event.target.value) || 1, 1))}
                    className="rounded-2xl border border-brand-200 px-4 py-3"
                  />
                </label>

                <div className="rounded-2xl border border-brand-100 bg-brand-50/70 p-4 text-sm text-stone-700">
                  <div>Début: {formatDateInputForDisplay(normalizedRentalStartDate)}</div>
                  <div>Fin prévue: {formatDateInputForDisplay(computedRentalEndDate)}</div>
                  <div className="mt-2 font-semibold text-brand-900">
                    Total prévisionnel: {formatEuroCents((product.effectivePriceCents ?? product.priceCents ?? 0) * (normalizedRentalMonths ?? 1))}
                  </div>
                </div>
              </div>

              <div className="mt-6 flex flex-wrap justify-end gap-3">
                <button
                  type="button"
                  className="inline-flex rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700"
                  onClick={() => setModalOpen(false)}
                >
                  Annuler
                </button>
                <button
                  type="button"
                  className="inline-flex rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white"
                  onClick={confirmRentalSelection}
                >
                  Ajouter la location
                </button>
              </div>
            </DialogPanel>
          </div>
        </div>
      </Dialog>
    </>
  );
};
