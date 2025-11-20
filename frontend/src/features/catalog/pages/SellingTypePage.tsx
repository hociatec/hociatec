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

    const params: { sellingType: 'sale' | 'rental'; q?: string } = {
      sellingType,
    };
    const trimmed = search.trim();
    if (trimmed.length > 0) {
      params.q = trimmed;
    }

    void fetchPublicProducts(params)
      .then((items) => setProducts(items))
      .catch((err: Error) => setError(err.message || 'Impossible de charger les produits.'))
      .finally(() => setLoading(false));
  }, [sellingType, search]);

  const total = useMemo(() => products.length, [products]);

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
              }}
            />
          }
        >
          <SearchFilter
            value={search}
            onChange={setSearch}
            placeholder="Rechercher par nom, description ou SKU..."
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
