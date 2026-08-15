export interface ProductVariantOption {
  id: number;
  slug: string;
  title: string;
  subtitle: string;
  groupLabel: string;
  groupValue: string | null;
  storage: string | null;
  color: string | null;
  accessibilityLabel: string;
  priceLabel: string;
  isAvailable: boolean;
  position: number;
}

export interface ProductVariantGroup {
  label: string;
  items: ProductVariantOption[];
}
