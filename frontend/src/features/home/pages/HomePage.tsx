import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useHomeFeaturedProducts } from '@/features/home/hooks/useHomeFeaturedProducts';
import { useHomeFeaturedServices } from '@/features/home/hooks/useHomeFeaturedServices';
import { useHomeLatestNews } from '@/features/home/hooks/useHomeLatestNews';
import { HomeFeaturedProductCard } from '@/features/home/components/HomeFeaturedProductCard';
import { HomeFeaturedServicesCarousel } from '@/features/home/components/HomeFeaturedServicesCarousel';
import {
  ORGANIZATION_SCHEMA,
  WEBSITE_SCHEMA,
  SITE_URL,
  LOCAL_BUSINESS_SCHEMA,
} from '@/shared/config/seoConfig';
import {
  HomeNewsCard,
  HomeNewsHeading,
  HomeProductsHeading,
  HomeServicesHeading,
} from '@/features/home/homeContent';
import '@/app/styles/home.css';

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

  const { services, loading: loadingServices, error: errorServices } = useHomeFeaturedServices();
  const {
    products,
    loading: loadingProducts,
    error: errorProducts,
  } = useHomeFeaturedProducts();
  const { articles, loading: loadingNews, error: errorNews } = useHomeLatestNews();

  return (
    <SiteLayout>
      <div className="home-page overflow-hidden">
        <section className="home-products animate-fade-in-up delay-300">
          <HomeServicesHeading />
          {loadingServices && (
            <p className="sr-only" role="status" aria-live="polite">
              Chargement des services...
            </p>
          )}
          {errorServices && (
            <div className="home-alert" role="alert">
              {errorServices}
            </div>
          )}
          {!loadingServices && !errorServices && services.length > 0 && (
            <HomeFeaturedServicesCarousel services={services} />
          )}
        </section>

        <section className="home-products animate-fade-in-up delay-300">
          <HomeProductsHeading />
          {loadingProducts && (
            <p className="sr-only" role="status" aria-live="polite">
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
              {products.map((product, index) => (
                <HomeFeaturedProductCard
                  key={product.id}
                  product={product}
                  imageLoading={index < 6 ? 'eager' : 'lazy'}
                />
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

        <section className="home-news animate-fade-in-up delay-300">
          <HomeNewsHeading />
          {loadingNews && (
            <p className="sr-only" role="status" aria-live="polite">
              Chargement des actualités...
            </p>
          )}
          {errorNews && (
            <div className="home-alert" role="alert">
              {errorNews}
            </div>
          )}
          {!loadingNews && !errorNews && articles.length > 0 && (
            <div className="home-news__grid">
              {articles.map((article) => (
                <HomeNewsCard key={article.id} article={article} />
              ))}
            </div>
          )}
          {!loadingNews && !errorNews && articles.length === 0 && (
            <div className="home-empty">
              <p>Aucune actualité disponible pour le moment</p>
              <span>Les publications Hociatec apparaîtront ici dès leur mise en ligne.</span>
            </div>
          )}
        </section>
      </div>
    </SiteLayout>
  );
};
