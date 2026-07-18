import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';

import {
  searchPublicProducts,
  type CatalogProduct,
  type CatalogSearchFacets,
  type CatalogSearchMeta,
} from '../api';
import { ProductActionToolbar } from '../components/ProductActionToolbar';
import { ProductCard } from '../components/ProductCard';
import { SiteFooter } from '@/shared/components/SiteFooter';
import { SiteHeader } from '@/shared/components/SiteHeader';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { NumberRangeFilter } from '@/shared/components/filters/NumberRangeFilter';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { SITE_URL } from '@/shared/config/seoConfig';

import './CatalogPages.css';

const ALL = 'all';

const toNullableNumber = (value: string | null) => {
  if (!value) return null;
  const parsed = Number(value);
  return Number.isNaN(parsed) || parsed < 0 ? null : parsed;
};

const normalizeParam = (value: string | null) => (value && value.trim() !== '' ? value : ALL);

export const CatalogSearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
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

  const query = searchParams.get('q') ?? '';
  const category = normalizeParam(searchParams.get('category'));
  const sellingType = normalizeParam(searchParams.get('sellingType'));
  const brand = normalizeParam(searchParams.get('brand'));
  const storageCapacity = normalizeParam(searchParams.get('storageCapacity'));
  const memoryRam = normalizeParam(searchParams.get('memoryRam'));
  const color = normalizeParam(searchParams.get('color'));
  const sort = searchParams.get('sort') ?? (query.trim() ? 'relevance' : 'release_year_desc');
  const inStock = searchParams.get('inStock') === '1';
  const minPrice = toNullableNumber(searchParams.get('minPrice'));
  const maxPrice = toNullableNumber(searchParams.get('maxPrice'));
  const page = Math.max(1, toNullableNumber(searchParams.get('page')) ?? 1);
  const perPage = 12;

  const updateParam = (key: string, value: string | null) => {
    const next = new URLSearchParams(searchParams);
    if (value === null || value === '' || value === ALL) {
      next.delete(key);
    } else {
      next.set(key, value);
    }
    if (key !== 'page') {
      next.delete('page');
    }
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
    const next = new URLSearchParams();
    if (query.trim()) {
      next.set('q', query.trim());
      next.set('sort', 'relevance');
    }
    setSearchParams(next, { replace: true });
  };

  useEffect(() => {
    setLoading(true);
    setError(null);

    void searchPublicProducts({
      q: query.trim() || undefined,
      category: category !== ALL ? category : undefined,
      sellingType: sellingType !== ALL ? (sellingType as 'sale' | 'rental') : undefined,
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
      .catch((err: Error) => setError(err.message || 'Impossible de charger les résultats.'))
      .finally(() => setLoading(false));
  }, [brand, category, color, inStock, maxPrice, memoryRam, minPrice, page, perPage, query, sellingType, sort, storageCapacity]);

  const categoryOptions = useMemo(
    () => [
      { value: ALL, label: 'Toutes les catégories' },
      ...facets.categories
        .map((item) => ({ value: item.extra ?? '', label: `${item.value} (${item.count})` }))
        .filter((item) => item.value !== ''),
    ],
    [facets.categories],
  );

  const brandOptions = useMemo(() => {
    return [
      { value: ALL, label: 'Toutes les marques' },
      ...facets.brands.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ];
  }, [facets.brands]);

  const storageOptions = useMemo(() => {
    return [
      { value: ALL, label: 'Tous les stockages' },
      ...facets.storageCapacities.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ];
  }, [facets.storageCapacities]);

  const memoryOptions = useMemo(() => {
    return [
      { value: ALL, label: 'Toutes les RAM' },
      ...facets.memoryRams.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ];
  }, [facets.memoryRams]);

  const colorOptions = useMemo(() => {
    return [
      { value: ALL, label: 'Toutes les couleurs' },
      ...facets.colors.map((item) => ({ value: item.value, label: `${item.value} (${item.count})` })),
    ];
  }, [facets.colors]);

  const pageNumbers = useMemo(() => {
    const start = Math.max(1, meta.page - 2);
    const end = Math.min(meta.totalPages, meta.page + 2);
    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  }, [meta.page, meta.totalPages]);

  const pageTitle = query.trim() ? `Recherche : ${query.trim()}` : 'Recherche catalogue';
  const canonicalUrl = `${SITE_URL}/catalogue/recherche`;
  const resultsSummary = query.trim()
    ? `${meta.total} résultat${meta.total > 1 ? 's' : ''} pour « ${query.trim()} »`
    : `${meta.total} résultat${meta.total > 1 ? 's' : ''}`;

  useDocumentTitle(`${pageTitle} - Catalogue`);
  useMetaTags({
    title: `${pageTitle} — Catalogue`,
    description: 'Recherche catalogue avancée par mot-clé, marque, prix, type et caractéristiques.',
    canonicalUrl,
  });

  return (
    <div className="site-layout">
      <SiteHeader variant="light" />
      <div className="site-layout__content">
        <div className="catalog-detail-layout">
        <Link to="/" className="catalog-page__breadcrumbs">
          Retour à l'accueil
        </Link>

        <header className="catalog-detail-header">
          <span className="catalog-badge">Recherche avancée</span>
          <h1>Rechercher dans le catalogue</h1>
          <div className="catalog-detail-metadata">
            <span>{resultsSummary}</span>
          </div>
          <p style={{ color: '#1e293b', maxWidth: 760 }}>
            Trouvez rapidement un produit par nom, marque, SKU, catégorie, prix et caractéristiques techniques.
          </p>
        </header>

        <section className="catalog-search-panel" aria-label="Filtres de recherche">
          <FilterBar
            className="catalog-filter-bar catalog-filter-bar--stacked"
            rightActions={<ResetFiltersButton onReset={resetFilters} />}
          >
            <SelectFilter value={category} onChange={(next) => updateParam('category', next)} options={categoryOptions} ariaLabel="Catégorie" />
            <SelectFilter
              value={sellingType}
              onChange={(next) => updateParam('sellingType', next)}
              options={[
                { value: ALL, label: 'Vente et location' },
                { value: 'sale', label: 'Vente' },
                { value: 'rental', label: 'Location' },
              ]}
              ariaLabel="Type de commercialisation"
            />
            <SelectFilter value={brand} onChange={(next) => updateParam('brand', next)} options={brandOptions} ariaLabel="Marque" />
            <NumberRangeFilter min={minPrice} max={maxPrice} onChange={updatePriceRange} step={50} />
            <SelectFilter value={storageCapacity} onChange={(next) => updateParam('storageCapacity', next)} options={storageOptions} ariaLabel="Stockage" />
            <SelectFilter value={memoryRam} onChange={(next) => updateParam('memoryRam', next)} options={memoryOptions} ariaLabel="Mémoire RAM" />
            <SelectFilter value={color} onChange={(next) => updateParam('color', next)} options={colorOptions} ariaLabel="Couleur" />
            <SelectFilter
              value={sort}
              onChange={(next) => updateParam('sort', next)}
              options={[
                { value: query.trim() ? 'relevance' : 'release_year_desc', label: query.trim() ? 'Pertinence' : 'Plus récents' },
                { value: 'release_year_desc', label: 'Du plus récent au moins récent' },
                { value: 'release_year_asc', label: 'Du moins récent au plus récent' },
                { value: 'price_asc', label: 'Prix croissant' },
                { value: 'price_desc', label: 'Prix décroissant' },
                { value: 'stock_desc', label: 'Stock le plus élevé' },
                { value: 'stock_asc', label: 'Stock le plus faible' },
                { value: 'name_desc', label: 'Nom Z → A' },
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
              Plage disponible: {facets.price.min !== null ? `${Math.round(facets.price.min / 100)} €` : '0 €'} à {facets.price.max !== null ? `${Math.round(facets.price.max / 100)} €` : '0 €'}
            </p>
          )}
        </section>

        {loading && <p>Chargement des résultats...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && (
          <>
            <section className="catalog-grid catalog-grid--products">
              {products.length === 0 ? (
                <div className="catalog-empty-state">
                  Aucun produit ne correspond à votre recherche ou à vos filtres.
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
              <nav className="catalog-pagination" aria-label="Pagination des résultats">
                <button
                  type="button"
                  className="catalog-pagination__button"
                  disabled={meta.page <= 1}
                  onClick={() => updateParam('page', String(meta.page - 1))}
                >
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
                <button
                  type="button"
                  className="catalog-pagination__button"
                  disabled={meta.page >= meta.totalPages}
                  onClick={() => updateParam('page', String(meta.page + 1))}
                >
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
