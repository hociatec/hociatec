import type { CatalogProduct } from '@/features/catalog/api';

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
  totalPriceCents: number;
  updatedAt: string;
}

export type CartStatus = 'idle' | 'loading' | 'ready';
