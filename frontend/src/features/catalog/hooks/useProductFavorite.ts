import type { CatalogProduct } from '@/features/catalog/api';
import { useFavoriteItem } from '@/features/favorites/publicApi';

export const useProductFavorite = (product?: CatalogProduct | null) => {
  return useFavoriteItem('product', product?.id);
};
