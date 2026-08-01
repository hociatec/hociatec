import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useHomeFeaturedProducts } from '@/features/home/hooks/useHomeFeaturedProducts';
import { HomeFeaturedProductCard } from '@/features/home/components/HomeFeaturedProductCard';
import {
  ORGANIZATION_SCHEMA,
  WEBSITE_SCHEMA,
  SITE_URL,
  LOCAL_BUSINESS_SCHEMA,
} from '@/shared/config/seoConfig';
import {
  HomeAudienceSection,
  HomeHeroSection,
  HomeProductsHeading,
  HomeStorySection,
  HomeTrustSection,
} from '@/features/home/homeContent';

export const HomePage = () => {
  useDocumentTitle('Informatique, réparation et services numériques');
  useMetaTags({
    title: 'Hociatec — Informatique, réparation et services numériques',
    description:
      "Vente de matériel informatique, réparation d'ordinateurs, maintenance informatique, création de site internet, assistance informatique et formation numérique.",
    type: 'website',
    canonicalUrl: SITE_URL,
    structuredData: [ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, LOCAL_BUSINESS_SCHEMA],
  });

  const { products, loading: loadingProducts, error: errorProducts } = useHomeFeaturedProducts();

  return (
    <SiteLayout>
      <div className="home-page overflow-hidden">
        <HomeHeroSection />
        <HomeAudienceSection />
        <HomeTrustSection />
        <HomeStorySection />

        <section className="home-products animate-fade-in-up delay-300">
          <HomeProductsHeading />
          {loadingProducts && (
            <p className="home-loading" role="status" aria-live="polite">
              Chargement des produits...
            </p>
          )}
          {errorProducts && (
            <div className="home-alert" role="alert">
              {errorProducts}
            </div>
          )}
          {!loadingProducts && !errorProducts && products.length > 0 && (
            <div className="home-products__grid">
              {products.map((product) => (
                <HomeFeaturedProductCard key={product.id} product={product} />
              ))}
            </div>
          )}
          {!loadingProducts && !errorProducts && products.length === 0 && (
            <div className="home-empty">
              <p>Aucun produit mis en avant pour le moment</p>
              <span>Les produits recommandés réapparaîtront ici dès que le catalogue sera mis à jour.</span>
            </div>
          )}
        </section>
      </div>
    </SiteLayout>
  );
};
