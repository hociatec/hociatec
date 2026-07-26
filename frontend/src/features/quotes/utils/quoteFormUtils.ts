import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';

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

type QuoteDraftForSave = {
  items?: QuoteItem[];
};

export const formatQuotePrice = formatEuroCents;
export const formatQuoteDate = formatDateInputForDisplay;

export const DEFAULT_QUOTE_CONDITIONS = `Le présent devis constitue une offre valable jusqu'à la date de fin de validité qui y figure. Il devient contractuel à compter de son acceptation expresse par le client.
Le devis est établi sur la base des informations communiquées par le client. Toute prestation, fourniture ou demande complémentaire non prévue au devis initial fera l'objet d'un accord écrit complémentaire ou d'un avenant.
Sauf stipulation particulière, les délais d'exécution ou de livraison sont indicatifs et courent à compter de la réception de l'acceptation du devis et, le cas échéant, de l'acompte prévu.
Sauf mention contraire, les prix sont exprimés en euros. Les taxes applicables sont celles en vigueur au jour de la facturation.
Pour les clients professionnels uniquement, tout retard de paiement pourra entraîner l'application de pénalités de retard exigibles sans rappel, calculées au taux de refinancement de la BCE majoré de 10 points, ainsi qu'une indemnité forfaitaire de 40 euros pour frais de recouvrement.
Pour les clients consommateurs, les garanties légales applicables demeurent celles prévues par la loi.`;

const toDateInputValue = (date: Date) => date.toISOString().slice(0, 10);

export const createDefaultQuoteValidity = () => {
  const validFrom = new Date();
  const validUntil = new Date(validFrom);
  validUntil.setDate(validUntil.getDate() + 30);

  return {
    validFrom: toDateInputValue(validFrom),
    validUntil: toDateInputValue(validUntil),
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
        description: baseDescription && baseDescription.length > 0
          ? `${baseDescription} - Durée: ${months} mois`
          : `Durée: ${months} mois`,
        unit: item.unit ?? 'mois',
        quantity: Math.max(1, item.quantity ?? 1) * months,
      };
    }),
  };
};
