import { useMemo } from 'react';

import type { CatalogSearchFacets, CatalogSort } from '@/features/catalog/api';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { NumberRangeFilter } from '@/shared/components/filters/NumberRangeFilter';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { formatEuroCentsRange } from '@/shared/lib/formatters';

const ALL = 'all';

type CategoryFiltersPanelProps = {
  search: string;
  brand: string;
  storageCapacity: string;
  memoryRam: string;
  color: string;
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
  storageCapacity,
  memoryRam,
  color,
  sort,
  minPrice,
  maxPrice,
  inStock,
  facets,
  updateParam,
  updatePriceRange,
  resetFilters,
}: CategoryFiltersPanelProps) => {
  const brandOptions = useMemo(
    () => [
      { value: ALL, label: 'Toutes les marques' },
      ...facets.brands.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ],
    [facets.brands],
  );
  const storageOptions = useMemo(
    () => [
      { value: ALL, label: 'Toutes les capacités' },
      ...facets.storageCapacities.map((item) => ({
        value: item.value,
        label: `${item.value} (${item.count})`,
      })),
    ],
    [facets.storageCapacities],
  );
  const memoryOptions = useMemo(
    () => [
      { value: ALL, label: 'Toutes les RAM' },
      ...facets.memoryRams.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ],
    [facets.memoryRams],
  );
  const colorOptions = useMemo(
    () => [
      { value: ALL, label: 'Toutes les couleurs' },
      ...facets.colors.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ],
    [facets.colors],
  );
  const showStorageFilter = facets.storageCapacities.length > 0 || storageCapacity !== ALL;
  const showMemoryFilter = facets.memoryRams.length > 0 || memoryRam !== ALL;
  const showColorFilter = facets.colors.length > 0 || color !== ALL;

  return (
    <section className="catalog-search-panel" aria-label="Filtres catégorie">
      <FilterBar
        className="catalog-filter-bar catalog-filter-bar--stacked"
        rightActions={<ResetFiltersButton onReset={resetFilters} />}
      >
        <SelectFilter
          value={brand}
          onChange={(next) => updateParam('brand', next)}
          options={brandOptions}
          ariaLabel="Marque"
        />
        <NumberRangeFilter
          min={minPrice}
          max={maxPrice}
          onChange={updatePriceRange}
          step={50}
        />
        {showStorageFilter && (
          <SelectFilter
            value={storageCapacity}
            onChange={(next) => updateParam('storageCapacity', next)}
            options={storageOptions}
            ariaLabel="Capacité de stockage"
          />
        )}
        {showMemoryFilter && (
          <SelectFilter
            value={memoryRam}
            onChange={(next) => updateParam('memoryRam', next)}
            options={memoryOptions}
            ariaLabel="Mémoire RAM"
          />
        )}
        {showColorFilter && (
          <SelectFilter
            value={color}
            onChange={(next) => updateParam('color', next)}
            options={colorOptions}
            ariaLabel="Couleur"
          />
        )}
        <SelectFilter
          value={sort}
          onChange={(next) => updateParam('sort', next)}
          options={[
            {
              value: search.trim() ? 'relevance' : 'release_year_desc',
              label: search.trim() ? 'Pertinence' : 'Plus récents',
            },
            ...(search.trim()
              ? [{ value: 'release_year_desc' as const, label: 'Du plus récent au moins récent' }]
              : []),
            { value: 'release_year_asc', label: 'Du moins récent au plus récent' },
            { value: 'price_asc', label: 'Prix croissant' },
            { value: 'price_desc', label: 'Prix décroissant' },
            { value: 'stock_desc', label: 'Stock le plus élevé' },
            { value: 'created_desc', label: 'Derniers ajoutés' },
          ]}
          ariaLabel="Tri"
        />
        <label className="catalog-filter-toggle">
          <input
            type="checkbox"
            checked={inStock}
            onChange={(event) => updateParam('inStock', event.target.checked ? '1' : null)}
          />
          <span>Uniquement en stock</span>
        </label>
      </FilterBar>
      {(facets.price.min !== null || facets.price.max !== null) && (
        <p className="catalog-search-panel__hint">
          Plage disponible: {formatEuroCentsRange(facets.price.min, facets.price.max)}
        </p>
      )}
    </section>
  );
};
