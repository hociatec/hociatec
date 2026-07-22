import type { CatalogProduct } from '../api';
import { formatFrenchDate, formatEuroCents } from '@/shared/lib/formatters';

export const formatProductPrice = formatEuroCents;
export const formatProductDate = formatFrenchDate;

export const buildVariantGroupKey = (product: CatalogProduct) =>
  product.variantGroup?.trim() ||
  product.name.replace(/\s*\([^)]*\)\s*$/u, '').replace(/\s*\([^)]*\)\s*$/u, '').trim() ||
  product.sku;
