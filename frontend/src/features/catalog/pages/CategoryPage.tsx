import { Link, useNavigate, useParams, useSearchParams } from 'react-router';

import { CategoryProductGrid } from '../components/CategoryProductGrid';
import { CategoryFiltersPanel } from '../components/CategoryFiltersPanel';
import { CategoryPagination } from '../components/CategoryPagination';
import { SiteFooter } from '@/shared/components/layout/SiteFooter';
import { SiteHeader } from '@/shared/components/layout/SiteHeader';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useCatalogMenu } from '@/features/catalog/hooks/useCatalogMenu';
import { useCategoryData } from '@/features/catalog/hooks/useCategoryData';
import { SITE_URL } from '@/shared/config/seoConfig';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { omitUndefinedProperties } from '@/shared/lib/object';
import { parseNullableNonNegativeInteger, parseNullablePositiveInteger } from '@/shared/lib/parsers';
import {
  ALL_CATALOG_FILTER,
  formatCatalogResultsSummary,
  normalizeCatalogFilter,
  normalizeCatalogSort,
} from '@/features/catalog/lib/catalogSearch';

import './CatalogPages.css';

export const CategoryPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const { categories: catalogCategories } = useCatalogMenu();
  const navigate = useNavigate();

  const search = searchParams.get('q') ?? '';
  const brand = normalizeCatalogFilter(searchParams.get('brand'));
  const storageCapacity = normalizeCatalogFilter(searchParams.get('storageCapacity'));
  const memoryRam = normalizeCatalogFilter(searchParams.get('memoryRam'));
  const color = normalizeCatalogFilter(searchParams.get('color'));
  const sort = normalizeCatalogSort(
    searchParams.get('sort'),
    search.trim() ? 'relevance' : 'release_year_desc',
  );
  const minPrice = parseNullableNonNegativeInteger(searchParams.get('minPrice'));
  const maxPrice = parseNullableNonNegativeInteger(searchParams.get('maxPrice'));
  const inStock = searchParams.get('inStock') === '1';
  const page = parseNullablePositiveInteger(searchParams.get('page')) ?? 1;
  const perPage = 12;
  const { data, products, meta, facets, loading, error, refresh } = useCategoryData({
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
  });
  const resultsSummary = formatCatalogResultsSummary(meta.total, search, 'solution');

  const canonicalUrl = slug ? `${SITE_URL}/catalogue/${slug}` : undefined;
  const collectionSchema = data
    ? {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: data.category.name,
        description: data.category.description ?? 'Découvrez nos solutions par catégorie.',
        url: canonicalUrl,
        mainEntity: {
          '@type': 'ItemList',
          numberOfItems: meta.total,
          itemListElement: products.map((product, index) => ({
            '@type': 'ListItem',
            position: index + 1 + (meta.page - 1) * meta.perPage,
            url: `${SITE_URL}/catalogue/produits/${product.slug}`,
            name: product.name,
          })),
        },
      }
    : undefined;

  useDocumentTitle(data?.category ? `${data.category.name} - Catalogue` : 'Catalogue - Catégorie');
  useMetaTags(omitUndefinedProperties({
    title: data?.category ? `${data.category.name} — Catalogue` : 'Catalogue - Catégorie',
    description: data?.category?.description ?? 'Découvrez nos solutions par catégorie.',
    type: 'website',
    canonicalUrl,
    structuredData: collectionSchema,
  }));

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams);
    if (value === null || value === '' || value === ALL_CATALOG_FILTER) next.delete(key);
    else next.set(key, value);
    if (key !== 'page') next.delete('page');
    setSearchParams(next, { replace: true });
  };

  const updatePriceRange = (nextRange: { min: number | null; max: number | null }) => {
    const next = new URLSearchParams(searchParams);
    if (nextRange.min === null) next.delete('minPrice');
    else next.set('minPrice', String(nextRange.min));
    if (nextRange.max === null) next.delete('maxPrice');
    else next.set('maxPrice', String(nextRange.max));
    next.delete('page');
    setSearchParams(next, { replace: true });
  };

  const resetFilters = () => {
    setSearchParams(
      new URLSearchParams(search.trim() ? { q: search.trim(), sort: 'relevance' } : {}),
      { replace: true },
    );
  };

  return (
    <div className="site-layout">
      <SiteHeader variant="light" />
      <div className="site-layout__content">
        <div className="catalog-detail-layout">
          <Link to="/" className="catalog-page__breadcrumbs">
            Retour à l'accueil
          </Link>

          {catalogCategories.length > 0 && (
            <nav className="catalog-category-nav" aria-label="Autres catégories">
              {catalogCategories.map((category) => (
                <button
                  key={category.id}
                  type="button"
                  className={[
                    'catalog-category-nav__item',
                    category.slug === slug ? 'is-active' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                  onClick={() => navigate(`/catalogue/${category.slug}`)}
                  aria-pressed={category.slug === slug}
                >
                  {category.name}
                </button>
              ))}
            </nav>
          )}

          {loading && <LoadingState>Chargement des produits de cette catégorie...</LoadingState>}
          {error && (
            <ErrorState onAction={() => void refresh()}>
              {error}
            </ErrorState>
          )}

          {!loading && !error && data && (
            <>
              <header className="catalog-detail-header">
                <span className="catalog-badge">Catégorie</span>
                <h1>{data.category.name}</h1>
                <div className="catalog-detail-metadata">
                  <span>{resultsSummary}</span>
                  <span>Actualisé le {formatOptionalFrenchDate(data.category.updatedAt)}</span>
                </div>
                {data.category.description && (
                  <p className="catalog-detail-description">{data.category.description}</p>
                )}
              </header>

              <CategoryFiltersPanel
                search={search}
                brand={brand}
                storageCapacity={storageCapacity}
                memoryRam={memoryRam}
                color={color}
                sort={sort}
                minPrice={minPrice}
                maxPrice={maxPrice}
                inStock={inStock}
                facets={facets}
                updateParam={updateParam}
                updatePriceRange={updatePriceRange}
                resetFilters={resetFilters}
              />

              <CategoryProductGrid products={products} />

              <CategoryPagination
                page={meta.page}
                totalPages={meta.totalPages}
                updatePage={(nextPage) => updateParam('page', String(nextPage))}
              />
            </>
          )}
        </div>
      </div>
      <SiteFooter />
    </div>
  );
};
