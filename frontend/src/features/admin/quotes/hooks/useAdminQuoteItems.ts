import { useMemo } from 'react';
import type { Dispatch, SetStateAction } from 'react';
import { clampAtLeast } from '@/shared/lib/number';

import type { CatalogProduct } from '@/features/catalog/adminApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import type { QuoteItem } from '@/features/quotes/publicApi';
import type { AdminQuoteFormState } from '@/features/admin/quotes/types/adminQuoteFormTypes';

type UseAdminQuoteItemsParams = {
  products: CatalogProduct[];
  quote: AdminQuoteFormState | null;
  services: QuoteServiceDto[];
  setQuote: Dispatch<SetStateAction<AdminQuoteFormState | null>>;
};

const createProductQuoteItem = (product: CatalogProduct): QuoteItem => ({
  type: 'product',
  productId: product.id,
  name: product.name,
  description: product.shortDescription ?? null,
  unit: null,
  quantity: 1,
  unitPriceCents: product.effectivePriceCents ?? product.priceCents,
  vatRate: 20,
  discountCents: 0,
  ...(product.sellingType === 'rental' ? { rentalMonths: 1 } : {}),
});

const createCustomQuoteItem = (): QuoteItem => ({
  type: 'custom',
  name: 'Ligne manuelle',
  description: '',
  unit: 'unité',
  quantity: 1,
  unitPriceCents: 0,
  vatRate: 20,
  discountCents: 0,
});

export const useAdminQuoteItems = ({
  products,
  quote,
  services,
  setQuote,
}: UseAdminQuoteItemsParams) => {
  const total = useMemo(() => {
    if (!quote) return { ht: 0, vat: 0, ttc: 0 };
    let ht = 0;
    let vat = 0;

    for (const item of quote.items) {
      const isRental =
        item.type === 'product' &&
        products.some(
          (product) => product.id === item.productId && product.sellingType === 'rental',
        );
      const months = isRental ? clampAtLeast(item.rentalMonths ?? 1, 1) : 1;
      const line = clampAtLeast(
        item.unitPriceCents * item.quantity * months - (item.discountCents ?? 0),
        0,
      );
      ht += line;
      vat += Math.round(line * (item.vatRate / 100));
    }

    ht = clampAtLeast(ht - (quote.discountCents ?? 0), 0);
    return { ht, vat, ttc: ht + vat + (quote.shippingCents ?? 0) };
  }, [products, quote]);

  const addItemFromService = (serviceId: number) => {
    const service = services.find((item) => item.id === serviceId);
    if (!service) return;

    setQuote((current) => {
      if (!current) return current;
      const index = current.items.findIndex(
        (item) => item.type === 'service' && item.serviceId === service.id,
      );
      if (index >= 0) {
        const next = [...current.items];
        const currentItem = next[index];
        if (!currentItem) return current;
        next[index] = { ...currentItem, quantity: (currentItem.quantity ?? 1) + 1 };
        return { ...current, items: next };
      }

      return {
        ...current,
        items: [
          ...current.items,
          {
            type: 'service',
              serviceId: service.id,
              name: service.title,
              description: service.description ?? null,
              unit: service.unit ?? null,
              quantity: 1,
              unitPriceCents: service.priceCents,
              vatRate: service.vatRate,
              discountCents: 0,
            },
          ],
      };
    });
  };

  const addItemFromProduct = (productId: number) => {
    const product = products.find((item) => item.id === productId);
    if (!product) return;

    setQuote((current) => {
      if (!current) return current;
      if (product.sellingType === 'rental') {
        return { ...current, items: [...current.items, createProductQuoteItem(product)] };
      }

      const index = current.items.findIndex(
        (item) => item.type === 'product' && item.productId === product.id,
      );
      if (index >= 0) {
        const next = [...current.items];
        const currentItem = next[index];
        if (!currentItem) return current;
        next[index] = { ...currentItem, quantity: (currentItem.quantity ?? 1) + 1 };
        return { ...current, items: next };
      }

      return { ...current, items: [...current.items, createProductQuoteItem(product)] };
    });
  };

  const addCustomItem = () => {
    setQuote((current) =>
      current ? { ...current, items: [...current.items, createCustomQuoteItem()] } : current,
    );
  };

  const updateItem = (index: number, patch: Partial<QuoteItem>) => {
    setQuote((current) =>
      current
        ? {
            ...current,
            items: current.items.map((item, itemIndex) =>
              itemIndex === index ? { ...item, ...patch } : item,
            ),
          }
        : current,
    );
  };

  const removeItem = (index: number) => {
    setQuote((current) =>
      current
        ? { ...current, items: current.items.filter((_, itemIndex) => itemIndex !== index) }
        : current,
    );
  };

  return { addCustomItem, addItemFromProduct, addItemFromService, removeItem, total, updateItem };
};
