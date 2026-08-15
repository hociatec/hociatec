import { CatalogFilters } from './CatalogFilters';
import type { CatalogSearchFacets, CatalogSort } from '@/features/catalog/api';

type CategoryFiltersPanelProps = {
  search: string;
  brand: string;
  attributeFilters: Record<string, string>;
  sort: CatalogSort;
  minPrice: number | null;
  maxPrice: number | null;
  inStock: boolean;
  facets: CatalogSearchFacets;
  updateParam: (key: string, value: string | null) => void;
  updatePriceRange: (range: { min: number | null; max: number | null }) => void;
  resetFilters: () => void;
};

export const CategoryFiltersPanel = ({
  search,
  brand,
  attributeFilters,
  sort,
  minPrice,
  maxPrice,
  inStock,
  facets,
  updateParam,
  updatePriceRange,
  resetFilters,
}: CategoryFiltersPanelProps) => (
  <CatalogFilters
    facets={facets}
    query={search}
    category="all"
    brand={brand}
    attributeFilters={attributeFilters}
    sort={sort}
    inStock={inStock}
    minPrice={minPrice}
    maxPrice={maxPrice}
    onParamChange={updateParam}
    onPriceChange={updatePriceRange}
    onReset={resetFilters}
  />
);
