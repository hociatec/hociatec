export interface ProductVariantOption {
  id: number;
  slug: string;
  title: string;
  subtitle: string;
  storage: string | null;
  color: string | null;
  priceLabel: string;
  isAvailable: boolean;
  position: number;
}

export interface ProductVariantGroup {
  storage: string;
  items: ProductVariantOption[];
}
