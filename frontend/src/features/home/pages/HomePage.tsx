import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useHomeFeaturedProducts } from '@/features/home/hooks/useHomeFeaturedProducts';
import { Link } from 'react-router';
import { HomeFeaturedProductCard } from '@/features/home/components/HomeFeaturedProductCard';
import {
  ArrowRight,
  Briefcase,
  CheckCircle2,
  Globe,
  GraduationCap,
  HeartHandshake,
  Monitor,
  ShieldCheck,
  Users,
  Wrench,
} from 'lucide-react';
import {
  ORGANIZATION_SCHEMA,
  WEBSITE_SCHEMA,
  SITE_URL,
  LOCAL_BUSINESS_SCHEMA,
} from '@/shared/config/seoConfig';

const heroServices = [
  {
    title: 'Vente de matériel informatique',
    Icon: Monitor,
  },
  {
    title: "Réparation d'ordinateurs",
    Icon: Wrench,
  },
  {
    title: 'Informatique professionnelle',
    Icon: Briefcase,
  },
  {
    title: 'Création de sites web',
    Icon: Globe,
  },
  {
    title: 'Maintenance informatique',
    Icon: ShieldCheck,
  },
  {
    title: 'Formation numérique',
    Icon: GraduationCap,
  },
];

const audienceSections = [
  {
    title: 'Pour les particuliers',
    description:
      "Des solutions simples pour s'équiper, réparer un appareil et être accompagné au quotidien.",
    ctaLabel: 'Voir les services',
    ctaTo: '/services',
    items: [
      'Vente de matériel informatique',
      "Réparation d'ordinateurs",
      'Ordinateurs reconditionnés',
      'Formation numérique',
      'Assistance informatique',
    ],
  },
  {
    title: 'Pour les professionnels',
    description:
      "Un accompagnement structuré pour fiabiliser le parc, gagner du temps et faire évoluer vos outils.",
    ctaLabel: 'Demander un devis',
    ctaTo: '/devis/nouveau',
    items: [
      'Maintenance informatique',
      'Gestion de parc informatique',
      'Développement de sites web',
      'Solutions numériques',
      'Accompagnement informatique',
    ],
  },
];

const trustItems = [
  'Produits garantis',
  'Accompagnement personnalisé',
  'Conseils avant achat',
  'Service de proximité',
  'Disponibilité',
  'Solutions adaptées aux besoins',
  'Suivi client',
  'Solutions durables',
];

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
        <section className="home-hero animate-fade-in-up">
          <div className="home-hero__content">
            <p className="home-hero__eyebrow">Informatique, réparation et accompagnement numérique</p>
            <h1>Matériel informatique, réparation, maintenance et création web.</h1>
            <p>
              Hociatec accompagne les particuliers et les professionnels avec des solutions
              informatiques utiles, durables et compréhensibles dès le premier échange.
            </p>
            <ul className="home-hero__service-list" aria-label="Services principaux">
              {heroServices.map(({ title, Icon }) => (
                <li key={title}>
                  <Icon aria-hidden="true" />
                  <span>{title}</span>
                </li>
              ))}
            </ul>
            <div className="home-hero__actions">
              <Link to="/devis/nouveau" className="home-button home-button--primary">
                Demander un devis
              </Link>
              <Link to="/contact" className="home-button home-button--secondary">
                Nous contacter
              </Link>
              <Link to="/services" className="home-button home-button--ghost">
                Découvrir nos services
              </Link>
            </div>
          </div>
          <div className="home-hero__visual" aria-hidden="true">
            <img src="/hociatec-hero-workbench.webp" alt="" />
            <div className="home-hero__metric">
              <strong>Des réponses concrètes pour chaque besoin.</strong>
              <span>
                Vente, réparation, assistance informatique, maintenance et création de site
                internet au même endroit.
              </span>
            </div>
          </div>
        </section>

        <section className="home-services animate-fade-in-up delay-100" aria-label="Parcours Hociatec">
          <div className="home-section-heading">
            <p>Des services pensés selon votre profil</p>
            <h2>Identifiez rapidement le bon accompagnement</h2>
          </div>
          <div className="home-services__grid home-services__grid--audiences">
            {audienceSections.map((section) => (
              <article key={section.title} className="home-service-card home-service-card--audience">
                <div className="home-service-card__body">
                  <h3>{section.title}</h3>
                  <p>{section.description}</p>
                  <ul className="home-service-card__list">
                    {section.items.map((item) => (
                      <li key={item}>
                        <CheckCircle2 aria-hidden="true" />
                        <span>{item}</span>
                      </li>
                    ))}
                  </ul>
                </div>
                <Link to={section.ctaTo} className="home-service-card__link">
                  {section.ctaLabel}
                  <ArrowRight aria-hidden="true" />
                </Link>
              </article>
            ))}
          </div>
        </section>

        <section className="home-trust animate-fade-in-up delay-200" aria-label="Éléments de réassurance">
          <div className="home-trust__panel">
            <div className="home-section-heading">
              <p>Des repères simples et transparents</p>
              <h2>Des engagements qui rassurent sans surpromesse</h2>
            </div>
            <div className="home-trust__grid">
              {trustItems.map((item) => (
                <div key={item} className="home-trust__item">
                  <ShieldCheck aria-hidden="true" />
                  <span>{item}</span>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section id="histoire" className="home-story animate-fade-in-up delay-300">
          <div className="home-story__content">
            <div className="home-section-heading">
              <p>Notre histoire</p>
              <h2>Un accompagnement humain avant, pendant et après l’intervention</h2>
            </div>
            <p>
              Hociatec mise sur la clarté, l’écoute et des conseils adaptés à chaque situation. Le
              besoin est compris avant de proposer un matériel, une réparation, une maintenance ou
              une solution numérique.
            </p>
            <div className="home-story__points">
              <div className="home-story__point">
                <Users aria-hidden="true" />
                <div>
                  <strong>Conseils adaptés</strong>
                  <span>Chaque demande est traitée selon l’usage, le budget et les contraintes réelles.</span>
                </div>
              </div>
              <div className="home-story__point">
                <HeartHandshake aria-hidden="true" />
                <div>
                  <strong>Suivi personnalisé</strong>
                  <span>Un interlocuteur reste disponible pour répondre et ajuster si nécessaire.</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section className="home-products animate-fade-in-up delay-300">
          <div className="home-section-heading home-section-heading--row">
            <div>
              <p>Une sélection mise en avant par Hociatec</p>
              <h2>Produits recommandés</h2>
            </div>
            <Link to="/catalogue/vente" className="home-products__link">
              Voir le catalogue
              <ArrowRight aria-hidden="true" />
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
              <span>Les produits recommandés réapparaîtront ici dès que le catalogue sera mis à jour.</span>
            </div>
          )}
        </section>
      </div>
    </SiteLayout>
  );
};
