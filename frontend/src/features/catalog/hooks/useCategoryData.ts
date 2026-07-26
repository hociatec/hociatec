import { useEffect, useState } from 'react';

import {
  fetchPublicCategory,
  searchPublicProducts,
  type CatalogProduct,
  type CatalogSearchFacets,
  type CatalogSearchMeta,
  type CatalogSort,
  type CategoryWithProducts,
} from '@/features/catalog/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

const emptyFacets: CatalogSearchFacets = {
  brands: [], categories: [], storageCapacities: [], memoryRams: [], colors: [], price: { min: null, max: null },
};
const initialMeta: CatalogSearchMeta = { page: 1, perPage: 12, total: 0, totalPages: 1 };

interface CategorySearchParams {
  slug?: string;
  search: string;
  brand: string;
  storageCapacity: string;
  memoryRam: string;
  color: string;
  minPrice: number | null;
  maxPrice: number | null;
  inStock: boolean;
  page: number;
  perPage: number;
  sort: CatalogSort;
}

export const useCategoryData = ({
  slug,
  search,
  brand,
  storageCapacity,
  memoryRam,
  color,
  minPrice,
  maxPrice,
  inStock,
  page,
  perPage,
  sort,
}: CategorySearchParams) => {
  const [data, setData] = useState<CategoryWithProducts | null>(null);
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [meta, setMeta] = useState(initialMeta);
  const [facets, setFacets] = useState(emptyFacets);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    let cancelled = false;
    setError(null);
    void fetchPublicCategory(slug)
      .then((result) => { if (!cancelled) setData(result); })
      .catch((reason) => {
        if (!cancelled) setError(getHttpErrorMessage(reason, "Cette catégorie n'est pas disponible pour le moment."));
      });
    return () => { cancelled = true; };
  }, [slug]);

  useEffect(() => {
    if (!slug) return;
    let cancelled = false;
    setLoading(true);
    setError(null);
    void searchPublicProducts({
      category: slug,
      q: search.trim() || undefined,
      brand: brand !== 'all' ? brand : undefined,
      storageCapacity: storageCapacity !== 'all' ? storageCapacity : undefined,
      memoryRam: memoryRam !== 'all' ? memoryRam : undefined,
      color: color !== 'all' ? color : undefined,
      minPrice: minPrice ?? undefined,
      maxPrice: maxPrice ?? undefined,
      inStock,
      page,
      perPage,
      sort,
    })
      .then((result) => {
        if (cancelled) return;
        setProducts(result.items);
        setMeta(result.meta);
        setFacets(result.facets);
      })
      .catch((reason) => {
        if (!cancelled) setError(getHttpErrorMessage(reason, "Les produits de cette catégorie n'ont pas pu être chargés."));
      })
      .finally(() => { if (!cancelled) setLoading(false); });
    return () => { cancelled = true; };
  }, [brand, color, inStock, maxPrice, memoryRam, minPrice, page, perPage, search, slug, sort, storageCapacity]);

  return { data, products, meta, facets, loading, error };
};
