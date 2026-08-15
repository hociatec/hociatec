import { Link } from 'react-router';
import { SiteFooter } from '@/shared/components/layout/SiteFooter';
import { SiteHeader } from '@/shared/components/layout/SiteHeader';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { CatalogFilters } from '../components/CatalogFilters';
import { CatalogProductResults } from '../components/CatalogProductResults';
import { useCatalogSearch } from '../hooks/useCatalogSearch';
import { formatGroupedCatalogResultsSummary } from '../lib/catalogSearch';
import './CatalogPages.css';

export interface SellingTypePageProps {
  sellingType: 'sale' | 'rental';
  title?: string;
}

export const SellingTypePage = ({ sellingType, title }: SellingTypePageProps) => {
  const catalog = useCatalogSearch({ sellingType });
  const pageTitle = title ?? (sellingType === 'rental' ? 'Location' : 'Vente');
  const description =
    sellingType === 'rental'
      ? 'Louez du matériel informatique prêt à l’emploi, avec une durée adaptée à votre besoin.'
      : 'Achetez du matériel informatique sélectionné, vérifié et prêt à être commandé.';
  const canonicalUrl = `${SITE_URL}/catalogue/${sellingType === 'rental' ? 'location' : 'vente'}`;
  useDocumentTitle(`${pageTitle} - Catalogue`);
  useMetaTags({ title: `${pageTitle} — Catalogue`, description, canonicalUrl });
  return (
    <div className="site-layout">
      <SiteHeader variant="light" />
      <div className="site-layout__content">
        <div className="catalog-detail-layout">
          <Link to="/" className="catalog-page__breadcrumbs">
            Retour à l'accueil
          </Link>
          <header className="catalog-detail-header">
            <span className="catalog-badge">{sellingType === 'rental' ? 'Location' : 'Vente'}</span>
            <h1>{pageTitle}</h1>
            <div className="catalog-detail-metadata">
              <span>
                {formatGroupedCatalogResultsSummary(
                  catalog.meta.total,
                  catalog.meta.variantTotal,
                  catalog.query,
                  'modèle',
                )}
              </span>
            </div>
          </header>
          <CatalogFilters
            facets={catalog.facets}
            query={catalog.query}
            category={catalog.category}
            brand={catalog.brand}
            attributeFilters={catalog.attributeFilters}
            sort={catalog.sort}
            inStock={catalog.inStock}
            minPrice={catalog.minPrice}
            maxPrice={catalog.maxPrice}
            includeCategory
            onParamChange={catalog.updateParam}
            onPriceChange={catalog.updatePriceRange}
            onReset={catalog.resetFilters}
          />
          <CatalogProductResults
            products={catalog.products}
            meta={catalog.meta}
            loading={catalog.loading}
            error={catalog.error}
            onRetry={() => void catalog.refresh()}
            loadingMessage="Chargement des produits disponibles..."
            emptyMessage="Aucun produit ne correspond à ces filtres. Retirez un critère ou consultez les autres catégories du catalogue."
            onPageChange={(page) => catalog.updateParam('page', String(page))}
          />
        </div>
      </div>
      <SiteFooter />
    </div>
  );
};
