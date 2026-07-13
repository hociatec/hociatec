import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { ProductCard } from '../components/ProductCard';
import { fetchPublicProducts, type CatalogProduct } from '../api';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { ProductCartActions } from '@/features/cart/components/ProductCartActions';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { ResetFiltersButton } from '@/shared/components/filters/ResetFiltersButton';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';

import './CatalogPages.css';

interface SellingTypePageProps {
  sellingType: 'sale' | 'rental';
  title?: string;
}

export const SellingTypePage = ({ sellingType, title }: SellingTypePageProps) => {
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [brand, setBrand] = useState('all');
  const [storageCapacity, setStorageCapacity] = useState('all');
  const [memoryRam, setMemoryRam] = useState('all');
  const [color, setColor] = useState('all');
  const [sort, setSort] = useState('release_year_desc');

  const pageTitle = title ?? (sellingType === 'rental' ? 'Location' : 'Vente');
  const canonicalUrl = `${SITE_URL}/catalogue/${sellingType === 'rental' ? 'location' : 'vente'}`;
  const pageDescription =
    sellingType === 'rental'
      ? 'Location de matériel informatique flexible et prête à l’emploi.'
      : 'Vente de matériel informatique sélectionné pour vos besoins.';

  useDocumentTitle(`${pageTitle} - Catalogue`);
  useMetaTags({
    title: `${pageTitle} — Catalogue`,
    description: pageDescription,
    canonicalUrl,
    structuredData: {
      '@context': 'https://schema.org',
      '@type': 'CollectionPage',
      name: `${pageTitle} — Catalogue`,
      description: pageDescription,
      url: canonicalUrl,
      mainEntity: {
        '@type': 'ItemList',
        numberOfItems: products.length,
        itemListElement: products.map((product, index) => ({
          '@type': 'ListItem',
          position: index + 1,
          name: product.name,
          url: `${SITE_URL}/catalogue/produits/${product.slug}`,
        })),
      },
    },
  });

  useEffect(() => {
    setLoading(true);
    setError(null);

    const params: {
      sellingType: 'sale' | 'rental';
      q?: string;
      brand?: string;
      storageCapacity?: string;
      memoryRam?: string;
      color?: string;
      sort?: 'price_asc' | 'price_desc' | 'release_year_desc' | 'release_year_asc';
    } = {
      sellingType,
      sort: sort as 'price_asc' | 'price_desc' | 'release_year_desc' | 'release_year_asc',
    };
    const trimmed = search.trim();
    if (trimmed.length > 0) {
      params.q = trimmed;
    }
    if (brand !== 'all') {
      params.brand = brand;
    }
    if (storageCapacity !== 'all') {
      params.storageCapacity = storageCapacity;
    }
    if (memoryRam !== 'all') {
      params.memoryRam = memoryRam;
    }
    if (color !== 'all') {
      params.color = color;
    }

    void fetchPublicProducts(params)
      .then((items) => setProducts(items))
      .catch((err: Error) => setError(err.message || 'Impossible de charger les produits.'))
      .finally(() => setLoading(false));
  }, [sellingType, search, brand, storageCapacity, memoryRam, color, sort]);

  const total = useMemo(() => products.length, [products]);
  const brandOptions = useMemo(() => {
    const brands = Array.from(new Set(products.map((product) => product.brand?.trim()).filter(Boolean) as string[]))
      .sort((a, b) => a.localeCompare(b, 'fr'));

    return [
      { value: 'all', label: 'Toutes les marques' },
      ...brands.map((item) => ({ value: item, label: item })),
    ];
  }, [products]);
  const storageOptions = useMemo(() => {
    const values = Array.from(new Set(products.map((product) => product.storageCapacity?.trim()).filter(Boolean) as string[]))
      .sort((a, b) => a.localeCompare(b, 'fr'));

    return [{ value: 'all', label: 'Toutes les capacités' }, ...values.map((item) => ({ value: item, label: item }))];
  }, [products]);
  const memoryOptions = useMemo(() => {
    const values = Array.from(new Set(products.map((product) => product.memoryRam?.trim()).filter(Boolean) as string[]))
      .sort((a, b) => a.localeCompare(b, 'fr'));

    return [{ value: 'all', label: 'Toutes les RAM' }, ...values.map((item) => ({ value: item, label: item }))];
  }, [products]);
  const colorOptions = useMemo(() => {
    const values = Array.from(new Set(products.map((product) => product.color?.trim()).filter(Boolean) as string[]))
      .sort((a, b) => a.localeCompare(b, 'fr'));

    return [{ value: 'all', label: 'Toutes les couleurs' }, ...values.map((item) => ({ value: item, label: item }))];
  }, [products]);

  return (
    <SiteLayout headerVariant="light">
      <div className="catalog-detail-layout">
        <Link to="/" className="catalog-page__breadcrumbs">
          Retour a l'accueil
        </Link>

        <header className="catalog-detail-header">
          <span className="catalog-badge">{sellingType === 'rental' ? 'Location' : 'Vente'}</span>
          <h1>{pageTitle}</h1>
          <div className="catalog-detail-metadata">
            <span>
              {total} produit{total > 1 ? 's' : ''} disponibles
            </span>
          </div>
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

        {loading && <p>Chargement des produits...</p>}
        {error && <div className="register-form__alert">{error}</div>}

        {!loading && !error && (
          <section className="catalog-grid catalog-grid--products">
            {products.length === 0 ? (
              <div className="catalog-empty-state">
                Aucun produit ne correspond a vos filtres.
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
        )}
      </div>
    </SiteLayout>
  );
};
