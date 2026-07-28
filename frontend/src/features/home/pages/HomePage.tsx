import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useHomeFeaturedProducts } from '@/features/home/hooks/useHomeFeaturedProducts';
import { Link } from 'react-router-dom';
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
    text: 'Vente, location et reconditionné avec une sélection fiable.',
  },
  {
    title: 'Interventions',
    text: 'Installation, configuration, assistance et formation selon le besoin.',
  },
  {
    title: 'Projets',
    text: 'Audits, devis, sites et logiciels cadrés avec une méthode claire.',
  },
];

const homeIntroPoints = [
  {
    label: 'Conseil avant achat',
    text: 'Un besoin cadré avant de choisir du matériel ou une prestation.',
  },
  {
    label: 'Solutions durables',
    text: 'Neuf, location, reprise ou reconditionné selon l usage réel.',
  },
  {
    label: 'Suivi clair',
    text: 'Commandes, devis, audits et rendez-vous restent faciles à retrouver.',
  },
];

const operatingModes = [
  'Matériel neuf, reconditionné ou en location',
  'Installation, reprise et valorisation des équipements',
  'Audits, devis, formations et projets web ou logiciels',
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
            <p className="home-hero__eyebrow">Informatique, services et accompagnement</p>
            <h1>Hociatec simplifie vos besoins numériques</h1>
            <p>
              Matériel, interventions, audits et projets sur mesure avec une approche claire,
              durable et adaptée à votre usage.
            </p>
            <div className="home-hero__actions">
              <Link to="/devis/nouveau" className="home-button home-button--primary">
                Demander un devis
              </Link>
              <Link to="/catalogue/vente" className="home-button home-button--secondary">
                Voir le catalogue
              </Link>
            </div>
            <div className="home-hero__summary" aria-label="Domaines couverts">
              <span>Matériel</span>
              <span>Services</span>
              <span>Audit</span>
              <span>Sur mesure</span>
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

        <section className="home-intro" aria-label="Présentation Hociatec">
          <div className="home-intro__copy">
            <p className="home-intro__eyebrow">Présentation</p>
            <h2>Une réponse informatique complète, sans complexité inutile</h2>
            <p>
              Hociatec accompagne les particuliers, indépendants et petites structures pour choisir,
              installer, maintenir ou faire évoluer leurs outils numériques.
            </p>
          </div>
          <div className="home-intro__panel">
            <h3>Prise en charge</h3>
            <ul>
              {operatingModes.map((mode) => (
                <li key={mode}>{mode}</li>
              ))}
            </ul>
          </div>
        </section>

        <section className="home-services" aria-label="Services Hociatec">
          <div className="home-section-heading">
            <p>Solutions Hociatec</p>
            <h2>Trois portes d’entrée selon votre besoin</h2>
          </div>
          <div className="home-services__grid">
            {serviceHighlights.map((service) => (
              <article key={service.title} className="home-service-card">
                <span>{service.title}</span>
                <p>{service.text}</p>
              </article>
            ))}
          </div>
        </section>

        <section className="home-feature-strip" aria-label="Engagements">
          {homeIntroPoints.map((point) => (
            <div key={point.label}>
              <strong>{point.label}</strong>
              <span>{point.text}</span>
            </div>
          ))}
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
