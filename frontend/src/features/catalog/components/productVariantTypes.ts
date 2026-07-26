export interface ProductVariantOption {
  id: number;
  slug: string;
  title: string;
  subtitle: string;
  storage: string | null;
  color: string | null;
  priceLabel: string;
  stockLabel: string;
  isAvailable: boolean;
}

export interface ProductVariantGroup {
  storage: string;
  items: ProductVariantOption[];
}
