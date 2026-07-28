import type { TradeInInput } from '../types';

export const emptyTradeInForm: TradeInInput = {
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  category: 'autre',
  productName: '',
  purchasePriceCents: 0,
  purchaseYear: new Date().getFullYear(),
  brand: '',
  model: '',
  serialNumber: '',
  conditionGrade: 'bon',
  functional: true,
  hasAccessories: false,
  hasProofOfPurchase: false,
  description: '',
  catalogProductId: null,
  consent: false,
  rib: null,
};

export const tradeInFieldClassName =
  'w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100';
