import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { ProductCard } from '../components/ProductCard';
import { fetchPublicCategory, type CategoryWithProducts } from '../api';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { useCatalogMenu } from '@/features/catalog/hooks/useCatalogMenu';
import { SITE_URL } from '@/shared/config/seoConfig';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';

import './CatalogPages.css';

export const CategoryPage = () => {
  const { slug } = useParams<{ slug: string }>();
  const [data, setData] = useState<CategoryWithProducts | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [brand, setBrand] = useState('all');
  const [storageCapacity, setStorageCapacity] = useState('all');
  const [memoryRam, setMemoryRam] = useState('all');
  const [color, setColor] = useState('all');
  const [sort, setSort] = useState('release_year_desc');
  const { categories: catalogCategories } = useCatalogMenu();
  const navigate = useNavigate();

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
          numberOfItems: data.products.length,
          itemListElement: data.products.map((product, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            url: `${SITE_URL}/catalogue/produits/${product.slug}`,
            name: product.name,
          })),
        },
      }
    : undefined;

  useDocumentTitle(
    data?.category ? `${data.category.name} - Catalogue` : 'Catalogue - Categorie',
  );
  useMetaTags({
    title: data?.category ? `${data.category.name} — Catalogue` : 'Catalogue - Catégorie',
    description: data?.category?.description ?? 'Découvrez nos solutions par catégorie.',
    type: 'website',
    canonicalUrl,
    structuredData: collectionSchema,
  });

  useEffect(() => {
    if (!slug) return;
    setLoading(true);
    setError(null);

    void fetchPublicCategory(slug)
      .then((result) => setData(result))
      .catch((err: Error) => setError(err.message || 'Categorie introuvable.'))
      .finally(() => setLoading(false));
  }, [slug]);

  const rawProducts = useMemo(() => data?.products ?? [], [data]);
  const brandOptions = useMemo(() => {
    const brands = Array.from(
      new Set(rawProducts.map((product) => product.brand?.trim()).filter(Boolean) as string[]),
    ).sort((a, b) => a.localeCompare(b, 'fr'));

    return [
      { value: 'all', label: 'Toutes les marques' },
      ...brands.map((item) => ({ value: item, label: item })),
    ];
  }, [rawProducts]);
  const storageOptions = useMemo(() => {
    const values = Array.from(
      new Set(rawProducts.map((product) => product.storageCapacity?.trim()).filter(Boolean) as string[]),
    ).sort((a, b) => a.localeCompare(b, 'fr'));

    return [{ value: 'all', label: 'Toutes les capacités' }, ...values.map((item) => ({ value: item, label: item }))];
  }, [rawProducts]);
  const memoryOptions = useMemo(() => {
    const values = Array.from(
      new Set(rawProducts.map((product) => product.memoryRam?.trim()).filter(Boolean) as string[]),
    ).sort((a, b) => a.localeCompare(b, 'fr'));

    return [{ value: 'all', label: 'Toutes les RAM' }, ...values.map((item) => ({ value: item, label: item }))];
  }, [rawProducts]);
  const colorOptions = useMemo(() => {
    const values = Array.from(
      new Set(rawProducts.map((product) => product.color?.trim()).filter(Boolean) as string[]),
    ).sort((a, b) => a.localeCompare(b, 'fr'));

    return [{ value: 'all', label: 'Toutes les couleurs' }, ...values.map((item) => ({ value: item, label: item }))];
  }, [rawProducts]);

  const products = useMemo(() => {
    const term = search.trim().toLowerCase();

    const filtered = rawProducts.filter((product) => {
      const matchSearch =
        term.length === 0 ||
        product.name.toLowerCase().includes(term) ||
        product.description.toLowerCase().includes(term) ||
        product.sku.toLowerCase().includes(term);

      const matchBrand = brand === 'all' || product.brand === brand;
      const matchStorage = storageCapacity === 'all' || product.storageCapacity === storageCapacity;
      const matchMemory = memoryRam === 'all' || product.memoryRam === memoryRam;
      const matchColor = color === 'all' || product.color === color;

      return matchSearch && matchBrand && matchStorage && matchMemory && matchColor;
    });

    return [...filtered].sort((left, right) => {
      switch (sort) {
        case 'price_asc':
          return left.priceCents - right.priceCents || left.name.localeCompare(right.name, 'fr');
        case 'price_desc':
          return right.priceCents - left.priceCents || left.name.localeCompare(right.name, 'fr');
        case 'release_year_asc':
          return (left.releaseYear ?? 0) - (right.releaseYear ?? 0) || left.name.localeCompare(right.name, 'fr');
        case 'release_year_desc':
        default:
          return (right.releaseYear ?? 0) - (left.releaseYear ?? 0) || left.name.localeCompare(right.name, 'fr');
      }
    });
  }, [rawProducts, search, brand, storageCapacity, memoryRam, color, sort]);

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link to="/" className="catalog-page__breadcrumbs">
          Retour a l'accueil
        </Link>

        {catalogCategories.length > 0 && (
          <nav className="catalog-category-nav" aria-label="Autres categories">
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

        {loading && <p>Chargement de la categorie...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && data && (
          <>
            <header className="catalog-detail-header">
              <span className="catalog-badge">Categorie</span>
              <h1>{data.category.name}</h1>
              <div className="catalog-detail-metadata">
                <span>
                  {products.length} solution{products.length > 1 ? 's' : ''} disponibles
                </span>
                <span>Actualise le {new Date(data.category.updatedAt).toLocaleDateString()}</span>
              </div>
              {data.category.description && (
                <p style={{ color: '#1e293b', maxWidth: 720 }}>{data.category.description}</p>
              )}
            </header>

            <FilterBar
              rightActions={
                <ResetFiltersButton
                  onReset={() => {
                    setSearch('');
                    setBrand('all');
                    setStorageCapacity('all');
                    setMemoryRam('all');
                    setColor('all');
                    setSort('release_year_desc');
                  }}
                />
              }
            >
              <SearchFilter
                value={search}
                onChange={setSearch}
                placeholder="Rechercher par nom, description ou SKU..."
              />
              <SelectFilter
                value={brand}
                onChange={setBrand}
                options={brandOptions}
                ariaLabel="Marque"
              />
              <SelectFilter
                value={storageCapacity}
                onChange={setStorageCapacity}
                options={storageOptions}
                ariaLabel="Capacité de stockage"
              />
              <SelectFilter
                value={memoryRam}
                onChange={setMemoryRam}
                options={memoryOptions}
                ariaLabel="Mémoire RAM"
              />
              <SelectFilter
                value={color}
                onChange={setColor}
                options={colorOptions}
                ariaLabel="Couleur"
              />
              <SelectFilter
                value={sort}
                onChange={setSort}
                options={[
                  { value: 'release_year_desc', label: 'Du plus récent au moins récent' },
                  { value: 'release_year_asc', label: 'Du moins récent au plus récent' },
                  { value: 'price_asc', label: 'Prix croissant' },
                  { value: 'price_desc', label: 'Prix décroissant' },
                ]}
                ariaLabel="Tri"
              />
            </FilterBar>

            <section className="catalog-grid catalog-grid--products">
              {products.length === 0 ? (
                <div className="catalog-empty-state">
                  Aucun produit n&apos;est publie dans cette categorie pour le moment.
                </div>
              ) : (
                products.map((product) => (
                  <ProductCard
                    key={product.id}
                    product={product}
                    actionSlot={<ProductCartActions product={product} />}
                  />
                ))
              )}
            </section>
          </>
        )}
      </div>
    </SiteLayout>
  );
};
