import { Link } from 'react-router';
import { SiteFooter } from '@/shared/components/SiteFooter';
import { SiteHeader } from '@/shared/components/SiteHeader';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { CatalogFilters } from '../components/CatalogFilters';
import { CatalogProductResults } from '../components/CatalogProductResults';
import { useCatalogSearch } from '../hooks/useCatalogSearch';
import { formatCatalogResultsSummary } from '../lib/catalogSearch';
import './CatalogPages.css';

export const CatalogSearchPage = () => {
  const catalog = useCatalogSearch();
  const pageTitle = catalog.query.trim()
    ? `Recherche : ${catalog.query.trim()}`
    : 'Recherche catalogue';
  useDocumentTitle(`${pageTitle} - Catalogue`);
  useMetaTags({
    title: `${pageTitle} — Catalogue`,
    description:
      'Affinez votre recherche par mot-clé, marque, prix, type de produit et caractéristiques techniques.',
    canonicalUrl: `${SITE_URL}/catalogue/recherche`,
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
            <h1>Trouvez le produit adapté à votre besoin</h1>
            <div className="catalog-detail-metadata">
              <span>
                {formatCatalogResultsSummary(catalog.meta.total, catalog.query, 'résultat')}
              </span>
            </div>
            <p className="catalog-detail-description">
              Filtrez le catalogue par usage, budget, stock et caractéristiques. Les résultats se
              mettent à jour automatiquement.
            </p>
          </header>
          <CatalogFilters
            facets={catalog.facets}
            query={catalog.query}
            category={catalog.category}
            brand={catalog.brand}
            storageCapacity={catalog.storageCapacity}
            memoryRam={catalog.memoryRam}
            color={catalog.color}
            sort={catalog.sort}
            inStock={catalog.inStock}
            minPrice={catalog.minPrice}
            maxPrice={catalog.maxPrice}
            includeCategory
            includeSellingType
            onParamChange={catalog.updateParam}
            onPriceChange={catalog.updatePriceRange}
            onReset={catalog.resetFilters}
          />
          <CatalogProductResults
            products={catalog.products}
            meta={catalog.meta}
            loading={catalog.loading}
            error={catalog.error}
            loadingMessage="Recherche des produits disponibles..."
            emptyMessage="Aucun produit ne correspond à ces critères. Retirez un filtre ou élargissez votre recherche pour afficher plus de résultats."
            onPageChange={(page) => catalog.updateParam('page', String(page))}
          />
        </div>
      </div>
      <SiteFooter />
    </div>
  );
};
