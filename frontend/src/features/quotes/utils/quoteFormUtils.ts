import {
  formatApiDateForDateInput,
  formatDateInputForDisplay,
  formatEuroCents,
} from '@/shared/lib/formatters';

export type QuoteItem = {
  id?: number;
  type: 'service' | 'product' | 'custom';
  productId?: number | null;
  serviceId?: number | null;
  name: string;
  description?: string | null;
  unit?: string | null;
  quantity: number;
  unitPriceCents: number;
  vatRate: number;
  discountCents?: number;
  rentalMonths?: number;
  sellingType?: 'sale' | 'rental';
};

export type QuoteTotals = {
  ht: number;
  vat: number;
  ttc: number;
};

export const calculateQuoteTotals = ({
  items = [],
  discountCents = 0,
  shippingCents = 0,
}: {
  items?: QuoteItem[];
  discountCents?: number;
  shippingCents?: number;
}): QuoteTotals => {
  let ht = 0;
  let vat = 0;

  for (const item of items) {
    const months = item.sellingType === 'rental' ? Math.max(1, item.rentalMonths ?? 1) : 1;
    const line = Math.max(
      0,
      (item.unitPriceCents ?? 0) * (item.quantity ?? 1) * months - (item.discountCents ?? 0),
    );
    ht += line;
    vat += Math.round(line * ((item.vatRate ?? 0) / 100));
  }

  const netHt = Math.max(0, ht - discountCents);
  return { ht: netHt, vat, ttc: netHt + vat + shippingCents };
};

type QuoteDraftForSave = {
  items?: QuoteItem[];
};

export const formatQuotePrice = formatEuroCents;
export const formatQuoteDate = formatDateInputForDisplay;

export const createDefaultQuoteValidity = () => {
  const validFrom = new Date();
  const validUntil = new Date(validFrom);
  validUntil.setDate(validUntil.getDate() + 30);

  return {
    validFrom: formatApiDateForDateInput(validFrom),
    validUntil: formatApiDateForDateInput(validUntil),
  };
};

export const adaptQuoteForSave = <T extends QuoteDraftForSave | null | undefined>(source: T) => {
  if (!source) return source;
  const items = source.items ?? [];

  return {
    ...source,
    items: items.map((item) => {
      if (item.type !== 'product' || !item.rentalMonths) {
        const { rentalMonths: _rentalMonths, ...rest } = item;
        return rest;
      }

      const months = Math.max(1, item.rentalMonths);
      const baseDescription = item.description?.trim();
      const { rentalMonths: _rentalMonths, ...rest } = item;

      return {
        ...rest,
        description:
          baseDescription && baseDescription.length > 0
            ? `${baseDescription} - Durée: ${months} mois`
            : `Durée: ${months} mois`,
        unit: item.unit ?? 'mois',
        quantity: Math.max(1, item.quantity ?? 1) * months,
      };
    }),
  };
};
