import type { CatalogCategory } from '@/features/catalog/adminApi';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { NumberRangeFilter } from '@/shared/components/filters/NumberRangeFilter';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';

export const AdminProductsFilters = ({
  categories,
  featuredFilter,
  filterCategory,
  hasActiveFilters,
  maxPrice,
  minPrice,
  search,
  sellingTypeFilter,
  sort,
  stockFilter,
  onFeaturedFilterChange,
  onFilterCategoryChange,
  onPriceRangeChange,
  onReset,
  onSearchChange,
  onSellingTypeFilterChange,
  onSortChange,
  onStockFilterChange,
}: {
  categories: CatalogCategory[];
  featuredFilter: 'all' | 'featured';
  filterCategory: string;
  hasActiveFilters: boolean;
  maxPrice: number | null;
  minPrice: number | null;
  search: string;
  sellingTypeFilter: 'all' | 'sale' | 'rental';
  sort: string;
  stockFilter: 'all' | 'low';
  onFeaturedFilterChange: (value: 'all' | 'featured') => void;
  onFilterCategoryChange: (value: string) => void;
  onPriceRangeChange: (next: { min: number | null; max: number | null }) => void;
  onReset: () => void;
  onSearchChange: (value: string) => void;
  onSellingTypeFilterChange: (value: 'all' | 'sale' | 'rental') => void;
  onSortChange: (value: string) => void;
  onStockFilterChange: (value: 'all' | 'low') => void;
}) => (
  <FilterBar rightActions={hasActiveFilters ? <ResetFiltersButton onReset={onReset} /> : null}>
    <SearchFilter
      value={search}
      onChange={onSearchChange}
      placeholder="Nom, SKU, slug ou marque..."
    />
    <SelectFilter
      value={filterCategory}
      onChange={onFilterCategoryChange}
      options={[
        { value: 'all', label: 'Toutes les catégories' },
        ...categories.map((category) => ({ value: category.slug, label: category.name })),
      ]}
      ariaLabel="Catégorie"
    />
    <SelectFilter
      value={stockFilter}
      onChange={(value) => onStockFilterChange(value as 'all' | 'low')}
      options={[
        { value: 'all', label: 'Tous les stocks' },
        { value: 'low', label: 'Stock faible (≤ 3)' },
      ]}
      ariaLabel="Stock"
    />
    <SelectFilter
      value={featuredFilter}
      onChange={(value) => onFeaturedFilterChange(value as 'all' | 'featured')}
      options={[
        { value: 'all', label: 'Tous les produits' },
        { value: 'featured', label: 'Produits tendance' },
      ]}
      ariaLabel="Mise en avant"
    />
    <SelectFilter
      value={sellingTypeFilter}
      onChange={(value) => onSellingTypeFilterChange(value as 'all' | 'sale' | 'rental')}
      options={[
        { value: 'all', label: 'Vente et location' },
        { value: 'sale', label: 'Vente uniquement' },
        { value: 'rental', label: 'Location uniquement' },
      ]}
      ariaLabel="Mode de vente"
    />
    <NumberRangeFilter min={minPrice} max={maxPrice} onChange={onPriceRangeChange} />
    <SelectFilter
      value={sort}
      onChange={onSortChange}
      options={[
        { value: 'created_desc', label: 'Plus récents' },
        { value: 'price_asc', label: 'Prix croissant' },
        { value: 'price_desc', label: 'Prix décroissant' },
        { value: 'name_desc', label: 'Nom décroissant' },
        { value: 'stock_desc', label: 'Stock décroissant' },
        { value: 'stock_asc', label: 'Stock croissant' },
      ]}
      ariaLabel="Trier les produits"
    />
  </FilterBar>
);
