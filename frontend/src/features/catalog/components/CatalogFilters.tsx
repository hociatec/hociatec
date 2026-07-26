import { useMemo } from 'react';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { NumberRangeFilter } from '@/shared/components/filters/NumberRangeFilter';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import type { CatalogSearchFacets, CatalogSort } from '../apiTypes';
import { ALL_CATALOG_FILTER } from '../lib/catalogSearch';

interface CatalogFiltersProps {
  facets: CatalogSearchFacets;
  query: string;
  category: string;
  brand: string;
  storageCapacity: string;
  memoryRam: string;
  color: string;
  sort: CatalogSort;
  inStock: boolean;
  minPrice: number | null;
  maxPrice: number | null;
  includeCategory?: boolean;
  includeSellingType?: boolean;
  sellingType?: 'sale' | 'rental' | typeof ALL_CATALOG_FILTER;
  onParamChange: (key: string, value: string | null) => void;
  onPriceChange: (range: { min: number | null; max: number | null }) => void;
  onReset: () => void;
}

export const CatalogFilters = ({ facets, query, category, brand, storageCapacity, memoryRam, color, sort, inStock, minPrice, maxPrice, includeCategory = false, includeSellingType = false, sellingType = ALL_CATALOG_FILTER, onParamChange, onPriceChange, onReset }: CatalogFiltersProps) => {
  const options = useMemo(() => ({
    category: [{ value: ALL_CATALOG_FILTER, label: 'Toutes les catégories' }, ...facets.categories.map((item) => ({ value: item.extra ?? '', label: `${item.value} (${item.count})` })).filter((item) => item.value)],
    brand: [{ value: ALL_CATALOG_FILTER, label: 'Toutes les marques' }, ...facets.brands.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    storage: [{ value: ALL_CATALOG_FILTER, label: 'Tous les stockages' }, ...facets.storageCapacities.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    memory: [{ value: ALL_CATALOG_FILTER, label: 'Toutes les RAM' }, ...facets.memoryRams.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    color: [{ value: ALL_CATALOG_FILTER, label: 'Toutes les couleurs' }, ...facets.colors.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
  }), [facets]);
  const show = { storage: facets.storageCapacities.length > 0 || storageCapacity !== ALL_CATALOG_FILTER, memory: facets.memoryRams.length > 0 || memoryRam !== ALL_CATALOG_FILTER, color: facets.colors.length > 0 || color !== ALL_CATALOG_FILTER };
  const sortOptions = [
    { value: query.trim() ? 'relevance' : 'release_year_desc', label: query.trim() ? 'Pertinence' : 'Plus récents' },
    ...(query.trim() ? [{ value: 'release_year_desc', label: 'Du plus récent au moins récent' }] : []),
    { value: 'release_year_asc', label: 'Du moins récent au plus récent' }, { value: 'price_asc', label: 'Prix croissant' },
    { value: 'price_desc', label: 'Prix décroissant' }, { value: 'stock_desc', label: 'Stock le plus élevé' },
    { value: 'stock_asc', label: 'Stock le plus faible' }, { value: 'name_desc', label: 'Nom Z → A' }, { value: 'created_desc', label: 'Derniers ajoutés' },
  ];
  return <section className="catalog-search-panel" aria-label="Filtres catalogue">
    <FilterBar className="catalog-filter-bar catalog-filter-bar--stacked" rightActions={<ResetFiltersButton onReset={onReset} />}>
      {includeCategory && <SelectFilter value={category} onChange={(value) => onParamChange('category', value)} options={options.category} ariaLabel="Catégorie" />}
      {includeSellingType && <SelectFilter value={sellingType} onChange={(value) => onParamChange('sellingType', value)} options={[{ value: ALL_CATALOG_FILTER, label: 'Vente et location' }, { value: 'sale', label: 'Vente' }, { value: 'rental', label: 'Location' }]} ariaLabel="Mode d'achat" />}
      <SelectFilter value={brand} onChange={(value) => onParamChange('brand', value)} options={options.brand} ariaLabel="Marque" />
      <NumberRangeFilter min={minPrice} max={maxPrice} onChange={onPriceChange} step={50} />
      {show.storage && <SelectFilter value={storageCapacity} onChange={(value) => onParamChange('storageCapacity', value)} options={options.storage} ariaLabel="Stockage" />}
      {show.memory && <SelectFilter value={memoryRam} onChange={(value) => onParamChange('memoryRam', value)} options={options.memory} ariaLabel="Mémoire RAM" />}
      {show.color && <SelectFilter value={color} onChange={(value) => onParamChange('color', value)} options={options.color} ariaLabel="Couleur" />}
      <SelectFilter value={sort} onChange={(value) => onParamChange('sort', value)} options={sortOptions} ariaLabel="Tri" />
      <label className="catalog-filter-toggle"><input type="checkbox" checked={inStock} onChange={(event) => onParamChange('inStock', event.target.checked ? '1' : null)} /><span>Uniquement en stock</span></label>
    </FilterBar>
    {(facets.price.min !== null || facets.price.max !== null) && <p className="catalog-search-panel__hint">Plage disponible: {facets.price.min !== null ? `${Math.round(facets.price.min / 100)} €` : '0 €'} à {facets.price.max !== null ? `${Math.round(facets.price.max / 100)} €` : '0 €'}</p>}
  </section>;
};
