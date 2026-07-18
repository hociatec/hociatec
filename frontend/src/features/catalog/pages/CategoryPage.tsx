import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';

import {
  fetchPublicCategory,
  searchPublicProducts,
  type CatalogProduct,
  type CatalogSearchFacets,
  type CatalogSearchMeta,
  type CategoryWithProducts,
} from '../api';
import { ProductActionToolbar } from '../components/ProductActionToolbar';
import { ProductCard } from '../components/ProductCard';
import { SiteFooter } from '../../../shared/components/SiteFooter';
import { SiteHeader } from '../../../shared/components/SiteHeader';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useCatalogMenu } from '@/features/catalog/hooks/useCatalogMenu';
import { SITE_URL } from '@/shared/config/seoConfig';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { NumberRangeFilter } from '@/shared/components/filters/NumberRangeFilter';

import './CatalogPages.css';

const ALL = 'all';

const toNullableNumber = (value: string | null) => {
  if (!value) return null;
  const parsed = Number(value);
  return Number.isNaN(parsed) || parsed < 0 ? null : parsed;
};

export const CategoryPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const [data, setData] = useState<CategoryWithProducts | null>(null);
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [meta, setMeta] = useState<CatalogSearchMeta>({ page: 1, perPage: 12, total: 0, totalPages: 1 });
  const [facets, setFacets] = useState<CatalogSearchFacets>({
    brands: [],
    categories: [],
    storageCapacities: [],
    memoryRams: [],
    colors: [],
    price: { min: null, max: null },
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const { categories: catalogCategories } = useCatalogMenu();
  const navigate = useNavigate();

  const search = searchParams.get('q') ?? '';
  const brand = searchParams.get('brand') ?? ALL;
  const storageCapacity = searchParams.get('storageCapacity') ?? ALL;
  const memoryRam = searchParams.get('memoryRam') ?? ALL;
  const color = searchParams.get('color') ?? ALL;
  const sort = searchParams.get('sort') ?? (search.trim() ? 'relevance' : 'release_year_desc');
  const minPrice = toNullableNumber(searchParams.get('minPrice'));
  const maxPrice = toNullableNumber(searchParams.get('maxPrice'));
  const inStock = searchParams.get('inStock') === '1';
  const page = Math.max(1, toNullableNumber(searchParams.get('page')) ?? 1);
  const perPage = 12;
  const resultsSummary = search.trim()
    ? `${meta.total} solution${meta.total > 1 ? 's' : ''} pour « ${search.trim()} »`
    : `${meta.total} solution${meta.total > 1 ? 's' : ''} disponible${meta.total > 1 ? 's' : ''}`;

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
  useMetaTags({
    title: data?.category ? `${data.category.name} — Catalogue` : 'Catalogue - Catégorie',
    description: data?.category?.description ?? 'Découvrez nos solutions par catégorie.',
    type: 'website',
    canonicalUrl,
    structuredData: collectionSchema,
  });

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams);
    if (value === null || value === '' || value === ALL) next.delete(key);
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

  useEffect(() => {
    if (!slug) return;
    void fetchPublicCategory(slug)
      .then((result) => setData(result))
      .catch((err: Error) => setError(err.message || 'Catégorie introuvable.'));
  }, [slug]);

  useEffect(() => {
    if (!slug) return;
    setLoading(true);
    setError(null);

    void searchPublicProducts({
      category: slug,
      q: search.trim() || undefined,
      brand: brand !== ALL ? brand : undefined,
      storageCapacity: storageCapacity !== ALL ? storageCapacity : undefined,
      memoryRam: memoryRam !== ALL ? memoryRam : undefined,
      color: color !== ALL ? color : undefined,
      minPrice: minPrice ?? undefined,
      maxPrice: maxPrice ?? undefined,
      inStock,
      page,
      perPage,
      sort: sort as any,
    })
      .then((result) => {
        setProducts(result.items);
        setMeta(result.meta);
        setFacets(result.facets);
      })
      .catch((err: Error) => setError(err.message || 'Catégorie introuvable.'))
      .finally(() => setLoading(false));
  }, [brand, color, inStock, maxPrice, memoryRam, minPrice, page, search, slug, sort, storageCapacity]);

  const brandOptions = useMemo(
    () => [{ value: ALL, label: 'Toutes les marques' }, ...facets.brands.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    [facets.brands],
  );
  const storageOptions = useMemo(
    () => [{ value: ALL, label: 'Toutes les capacités' }, ...facets.storageCapacities.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    [facets.storageCapacities],
  );
  const memoryOptions = useMemo(
    () => [{ value: ALL, label: 'Toutes les RAM' }, ...facets.memoryRams.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    [facets.memoryRams],
  );
  const colorOptions = useMemo(
    () => [{ value: ALL, label: 'Toutes les couleurs' }, ...facets.colors.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` }))],
    [facets.colors],
  );
  const pageNumbers = useMemo(() => {
    const start = Math.max(1, meta.page - 2);
    const end = Math.min(meta.totalPages, meta.page + 2);
    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  }, [meta.page, meta.totalPages]);

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
                className={['catalog-category-nav__item', category.slug === slug ? 'is-active' : ''].filter(Boolean).join(' ')}
                onClick={() => navigate(`/catalogue/${category.slug}`)}
                aria-pressed={category.slug === slug}
              >
                {category.name}
              </button>
            ))}
          </nav>
        )}

        {loading && <p>Chargement de la catégorie...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && data && (
          <>
            <header className="catalog-detail-header">
              <span className="catalog-badge">Catégorie</span>
              <h1>{data.category.name}</h1>
              <div className="catalog-detail-metadata">
                <span>{resultsSummary}</span>
                <span>Actualisé le {new Date(data.category.updatedAt).toLocaleDateString()}</span>
              </div>
              {data.category.description && (
                <p style={{ color: '#1e293b', maxWidth: 720 }}>{data.category.description}</p>
              )}
            </header>

            <section className="catalog-search-panel" aria-label="Filtres catégorie">
              <FilterBar
                className="catalog-filter-bar catalog-filter-bar--stacked"
                rightActions={
                  <ResetFiltersButton
                    onReset={() => {
                      setSearchParams(new URLSearchParams(search.trim() ? { q: search.trim(), sort: 'relevance' } : {}), { replace: true });
                    }}
                  />
                }
              >
                <SelectFilter value={brand} onChange={(next) => updateParam('brand', next)} options={brandOptions} ariaLabel="Marque" />
                <NumberRangeFilter min={minPrice} max={maxPrice} onChange={updatePriceRange} step={50} />
                <SelectFilter value={storageCapacity} onChange={(next) => updateParam('storageCapacity', next)} options={storageOptions} ariaLabel="Capacité de stockage" />
                <SelectFilter value={memoryRam} onChange={(next) => updateParam('memoryRam', next)} options={memoryOptions} ariaLabel="Mémoire RAM" />
                <SelectFilter value={color} onChange={(next) => updateParam('color', next)} options={colorOptions} ariaLabel="Couleur" />
                <SelectFilter
                  value={sort}
                  onChange={(next) => updateParam('sort', next)}
                  options={[
                    { value: search.trim() ? 'relevance' : 'release_year_desc', label: search.trim() ? 'Pertinence' : 'Plus récents' },
                    { value: 'release_year_desc', label: 'Du plus récent au moins récent' },
                    { value: 'release_year_asc', label: 'Du moins récent au plus récent' },
                    { value: 'price_asc', label: 'Prix croissant' },
                    { value: 'price_desc', label: 'Prix décroissant' },
                    { value: 'stock_desc', label: 'Stock le plus élevé' },
                    { value: 'created_desc', label: 'Derniers ajoutés' },
                  ]}
                  ariaLabel="Tri"
                />
                <label className="catalog-filter-toggle">
                  <input type="checkbox" checked={inStock} onChange={(event) => updateParam('inStock', event.target.checked ? '1' : null)} />
                  <span>Uniquement en stock</span>
                </label>
              </FilterBar>
              {(facets.price.min !== null || facets.price.max !== null) && (
                <p className="catalog-search-panel__hint">
                  Plage disponible: {facets.price.min !== null ? `${Math.round(facets.price.min / 100)} €` : '0 €'} à {facets.price.max !== null ? `${Math.round(facets.price.max / 100)} €` : '0 €'}
                </p>
              )}
            </section>

            <section className="catalog-grid catalog-grid--products">
              {products.length === 0 ? (
                <div className="catalog-empty-state">
                  Aucun produit ne correspond à cette catégorie avec vos filtres.
                </div>
              ) : (
                products.map((product) => (
                  <ProductCard
                    key={product.id}
                    product={product}
                    actionSlot={<ProductActionToolbar product={product} />}
                  />
                ))
              )}
            </section>

            {meta.totalPages > 1 && (
              <nav className="catalog-pagination" aria-label="Pagination des produits">
                <button type="button" className="catalog-pagination__button" disabled={meta.page <= 1} onClick={() => updateParam('page', String(meta.page - 1))}>
                  Précédent
                </button>
                {pageNumbers.map((pageNumber) => (
                  <button
                    key={pageNumber}
                    type="button"
                    className={`catalog-pagination__button${pageNumber === meta.page ? ' is-active' : ''}`}
                    onClick={() => updateParam('page', String(pageNumber))}
                    aria-current={pageNumber === meta.page ? 'page' : undefined}
                  >
                    {pageNumber}
                  </button>
                ))}
                <button type="button" className="catalog-pagination__button" disabled={meta.page >= meta.totalPages} onClick={() => updateParam('page', String(meta.page + 1))}>
                  Suivant
                </button>
              </nav>
            )}
          </>
        )}
        </div>
      </div>
      <SiteFooter />
    </div>
  );
};
