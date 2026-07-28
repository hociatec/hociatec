import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useHomeFeaturedProducts } from '@/features/home/hooks/useHomeFeaturedProducts';
import { Link } from 'react-router';
import { HomeFeaturedProductCard } from '@/features/home/components/HomeFeaturedProductCard';
import {
  ORGANIZATION_SCHEMA,
  WEBSITE_SCHEMA,
  SITE_URL,
  LOCAL_BUSINESS_SCHEMA,
} from '@/shared/config/seoConfig';

const serviceHighlights = [
  {
    title: 'Matériel',
    text: 'Neuf, reconditionné ou en location, selon votre usage.',
  },
  {
    title: 'Interventions',
    text: 'Installation, configuration, assistance et formation.',
  },
  {
    title: 'Projets',
    text: 'Audits, devis, sites et logiciels avec un cadre clair.',
  },
];

export const HomePage = () => {
  useDocumentTitle('Le numérique à taille humaine');
  useMetaTags({
    title: 'Hociatec — Le numérique à taille humaine',
    description:
      'Vente/location de matériel, formation, conception, audits. Une approche accessible, durable et pensée pour vous.',
    type: 'website',
    canonicalUrl: SITE_URL,
    structuredData: [ORGANIZATION_SCHEMA, WEBSITE_SCHEMA, LOCAL_BUSINESS_SCHEMA],
  });

  const { products, loading: loadingProducts, error: errorProducts } = useHomeFeaturedProducts();

  return (
    <SiteLayout>
      <div className="home-page">
        <section className="home-hero">
          <div className="home-hero__content">
            <h1>Matériel, interventions et projets numériques</h1>
            <p>
              Des solutions adaptées à votre usage, avec un interlocuteur pour vous guider de
              l’idée au suivi.
            </p>
            <div className="home-hero__actions">
              <Link to="/devis/nouveau" className="home-button home-button--primary">
                Demander un devis
              </Link>
              <Link to="/catalogue/vente" className="home-button home-button--secondary">
                Voir le catalogue
              </Link>
            </div>
            <div className="home-hero__summary" aria-label="Engagements Hociatec">
              <span>Conseil humain</span>
              <span>Solutions durables</span>
              <span>Suivi clair</span>
            </div>
          </div>
          <div className="home-hero__visual" aria-hidden="true">
            <img src="/hociatec-hero-workbench.webp" alt="" />
            <div className="home-hero__metric">
              <strong>Vente · Location · Audit</strong>
              <span>Un interlocuteur pour avancer plus vite.</span>
            </div>
          </div>
        </section>

        <section className="home-services" aria-label="Services Hociatec">
          <div className="home-services__grid">
            {serviceHighlights.map((service) => (
              <article key={service.title} className="home-service-card">
                <span>{service.title}</span>
                <p>{service.text}</p>
              </article>
            ))}
          </div>
        </section>

        <section className="home-products">
          <div className="home-section-heading home-section-heading--row">
            <div>
              <p>Catalogue</p>
              <h2>Produits tendances</h2>
            </div>
            <Link to="/catalogue/vente" className="home-button home-button--secondary">
              Tous les produits
            </Link>
          </div>
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
              <span>
                Les produits tendances réapparaîtront ici dès que le catalogue sera réalimenté.
              </span>
            </div>
          )}
        </section>
      </div>
    </SiteLayout>
  );
};
