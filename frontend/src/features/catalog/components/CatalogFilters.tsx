import { useMemo } from 'react';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { NumberRangeFilter } from '@/shared/components/filters/NumberRangeFilter';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { formatEuroCentsRange } from '@/shared/lib/formatters';
import type { CatalogSearchFacets, CatalogSort } from '../apiTypes';
import { ALL_CATALOG_FILTER } from '../lib/catalogSearch';

interface CatalogFiltersProps {
  facets: CatalogSearchFacets;
  query: string;
  category: string;
  brand: string;
  attributeFilters: Record<string, string>;
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

export const CatalogFilters = ({
  facets,
  query,
  category,
  brand,
  attributeFilters,
  sort,
  inStock,
  minPrice,
  maxPrice,
  includeCategory = false,
  includeSellingType = false,
  sellingType = ALL_CATALOG_FILTER,
  onParamChange,
  onPriceChange,
  onReset,
}: CatalogFiltersProps) => {
  const allAttributeLabel = (label: string) => {
    const normalized = label.trim().toLowerCase();

    return matchAttributeFilterLabel(normalized);
  };

  const matchAttributeFilterLabel = (label: string) => {
    return ({
      couleur: 'Toutes les couleurs',
      marque: 'Toutes les marques',
      categorie: 'Toutes les catégories',
      catégorie: 'Toutes les catégories',
      stockage: 'Tous les stockages',
    } as Record<string, string>)[label] ?? `Tous les ${label}s`;
  };

  const appendUnavailableSelectedOption = (
    currentValue: string,
    fallbackValue: string,
    values: Array<{ value: string; label: string }>,
  ) => {
    if (currentValue === fallbackValue || values.some((item) => item.value === currentValue)) {
      return values;
    }

    return [
      ...values,
      { value: currentValue, label: `${currentValue} (indisponible)` },
    ];
  };
  const options = useMemo(
    () => ({
      category: appendUnavailableSelectedOption(category, ALL_CATALOG_FILTER, [
        { value: ALL_CATALOG_FILTER, label: 'Toutes les catégories' },
        ...facets.categories
          .map((item) => ({ value: item.extra ?? '', label: `${item.value} (${item.count})` }))
          .filter((item) => item.value),
      ]),
      brand: appendUnavailableSelectedOption(brand, ALL_CATALOG_FILTER, [
        { value: ALL_CATALOG_FILTER, label: 'Toutes les marques' },
        ...facets.brands.map((item) => ({
          value: item.value,
          label: `${item.value} (${item.count})`,
        })),
      ]),
    }),
    [brand, category, facets],
  );
  const sortOptions = [
    {
      value: query.trim() ? 'relevance' : 'release_year_desc',
      label: query.trim() ? 'Pertinence' : 'Du plus récent au moins récent',
    },
    ...(query.trim()
      ? [{ value: 'release_year_desc', label: 'Du plus récent au moins récent' }]
      : []),
    { value: 'release_year_asc', label: 'Du moins récent au plus récent' },
    { value: 'price_asc', label: 'Du moins cher au plus cher' },
    { value: 'price_desc', label: 'Du plus cher au moins cher' },
    { value: 'stock_desc', label: 'Stock le plus élevé' },
    { value: 'stock_asc', label: 'Stock le moins élevé' },
    { value: 'name_asc', label: 'De A à Z' },
    { value: 'name_desc', label: 'De Z à A' },
    { value: 'created_desc', label: 'Derniers ajoutés' },
  ];
  return (
    <section className="catalog-search-panel" aria-label="Filtres catalogue">
      <FilterBar
        className="catalog-filter-bar catalog-filter-bar--stacked"
        rightActions={<ResetFiltersButton onReset={onReset} />}
      >
        {includeCategory && (
          <SelectFilter
            value={category}
            onChange={(value) => onParamChange('category', value)}
            options={options.category}
            ariaLabel="Catégorie"
          />
        )}
        {includeSellingType && (
          <SelectFilter
            value={sellingType}
            onChange={(value) => onParamChange('sellingType', value)}
            options={[
              { value: ALL_CATALOG_FILTER, label: 'Vente et location' },
              { value: 'sale', label: 'Vente' },
              { value: 'rental', label: 'Location' },
            ]}
            ariaLabel="Mode d'achat"
          />
        )}
        <SelectFilter
          value={brand}
          onChange={(value) => onParamChange('brand', value)}
          options={options.brand}
          ariaLabel="Marque"
        />
        <NumberRangeFilter min={minPrice} max={maxPrice} onChange={onPriceChange} step={50} />
        {facets.attributes.map((attribute) => (
          <SelectFilter
            key={attribute.code}
            value={attributeFilters[attribute.code] ?? ALL_CATALOG_FILTER}
            onChange={(value) => onParamChange(`attribute_${attribute.code}`, value)}
            options={appendUnavailableSelectedOption(
              attributeFilters[attribute.code] ?? ALL_CATALOG_FILTER,
              ALL_CATALOG_FILTER,
              [
                { value: ALL_CATALOG_FILTER, label: allAttributeLabel(attribute.label) },
                ...attribute.values.map((item) => ({
                  value: item.value,
                  label: `${item.value} (${item.count})`,
                })),
              ],
            )}
            ariaLabel={attribute.label}
          />
        ))}
        <SelectFilter
          value={sort}
          onChange={(value) => onParamChange('sort', value)}
          options={sortOptions}
          ariaLabel="Tri"
        />
        <label className="catalog-filter-toggle">
          <input
            type="checkbox"
            checked={inStock}
            onChange={(event) => onParamChange('inStock', event.target.checked ? '1' : null)}
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
