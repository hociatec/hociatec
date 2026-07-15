import type { CatalogProduct } from '@/features/catalog/api';

export interface CartPromotion {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  discountType: 'percent' | 'fixed_cents';
  discountValue: number;
  discountAmountCents: number;
  audienceKey: string;
  criteria: Record<string, string | number | boolean>;
  isActive: boolean;
  startsAt?: string | null;
  endsAt?: string | null;
}

export interface CartItem {
  id: number;
  product: CatalogProduct;
  quantity: number;
  linePriceCents: number;
  rentalMonths?: number | null;
}

export interface Cart {
  token: string;
  items: CartItem[];
  totalQuantity: number;
  subtotalPriceCents: number;
  discountAmountCents: number;
  totalPriceCents: number;
  appliedPromotion: CartPromotion | null;
  eligiblePromotions: CartPromotion[];
  updatedAt: string;
}

export type CartStatus = 'idle' | 'loading' | 'ready';
