import type { CatalogProduct } from '@/features/catalog/api';

export interface CartItem {
  product: CatalogProduct;
  quantity: number;
  linePriceCents: number;
}

export interface Cart {
  token: string;
  items: CartItem[];
  totalQuantity: number;
  totalPriceCents: number;
  updatedAt: string;
}

export type CartStatus = 'idle' | 'loading' | 'ready';
