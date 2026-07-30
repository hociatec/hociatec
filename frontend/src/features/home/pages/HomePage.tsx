import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useHomeFeaturedProducts } from '@/features/home/hooks/useHomeFeaturedProducts';
import { Link } from 'react-router';
import { HomeFeaturedProductCard } from '@/features/home/components/HomeFeaturedProductCard';
import { Star, ShieldCheck, Users, Award, HeartHandshake } from 'lucide-react';
import {
  ORGANIZATION_SCHEMA,
  WEBSITE_SCHEMA,
  SITE_URL,
  LOCAL_BUSINESS_SCHEMA,
} from '@/shared/config/seoConfig';

const serviceHighlights = [
  {
    title: 'Matériel',
    text: 'Une sélection cohérente, du neuf au reconditionné, à l’achat ou en location.',
  },
  {
    title: 'Interventions',
    text: 'Installation, dépannage et configuration pour retrouver un quotidien fluide.',
  },
  {
    title: 'Projets',
    text: 'Audit, site ou outil métier : des étapes claires et un résultat utile.',
  },
];

const stats = [
  { value: '1500+', label: 'Ordinateurs reconditionnés', icon: ShieldCheck },
  { value: '10+', label: "Ans d'expérience", icon: Award },
  { value: '98%', label: 'Clients satisfaits', icon: Users },
  { value: '24h', label: 'Délai moyen de réponse', icon: HeartHandshake },
];

const clientReviews = [
  {
    name: 'Jean-Pierre M.',
    role: 'Particulier',
    rating: 5,
    comment: 'Un service impeccable et rapide. Mon ordinateur reconditionné fonctionne comme neuf ! Un grand merci pour les conseils avisés.',
  },
  {
    name: 'Sophie L.',
    role: 'Directrice d’école de formation',
    rating: 5,
    comment: "Excellent matériel de formation loué pour nos sessions. Support réactif et professionnel, je recommande les yeux fermés.",
  },
  {
    name: 'Cabinet Bertrand & Associés',
    role: 'Client Professionnel',
    rating: 5,
    comment: 'L’audit et la restructuration de notre réseau ont été menés de main de maître. Simplicité, clarté et efficacité maximales.',
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
      <div className="home-page overflow-hidden">
        <section className="home-hero animate-fade-in-up">
          <div className="home-hero__content">
            <h1>Un numérique fiable, pensé pour durer.</h1>
            <p>
              Le bon équipement, des outils bien configurés et des projets qui avancent sans
              complexité inutile.
            </p>
            <div className="home-hero__actions">
              <Link to="/devis/nouveau" className="home-button home-button--primary">
                Demander un devis
              </Link>
              <Link to="/catalogue/vente" className="home-button home-button--secondary">
                Voir le catalogue
              </Link>
            </div>
          </div>
          <div className="home-hero__visual" aria-hidden="true">
            <img src="/hociatec-hero-workbench.webp" alt="" />
            <div className="home-hero__metric">
              <strong>Des choix utiles. Des réponses concrètes.</strong>
              <span>Un accompagnement qui reste compréhensible à chaque étape.</span>
            </div>
          </div>
        </section>

        <section className="home-services animate-fade-in-up delay-100" aria-label="Services Hociatec">
          <div className="home-services__grid">
            {serviceHighlights.map((service) => (
              <article key={service.title} className="home-service-card">
                <p>{service.text}</p>
              </article>
            ))}
          </div>
        </section>

        {/* Stats Section */}
        <section className="home-stats-section animate-fade-in-up delay-200" aria-label="Quelques chiffres">
          <div className="home-stats-container">
            <div className="home-stats-grid">
              {stats.map((stat) => {
                const IconComponent = stat.icon;
                return (
                  <div key={stat.label} className="home-stat-item">
                    <div className="home-stat-icon-wrapper">
                      <IconComponent className="home-stat-icon" aria-hidden="true" />
                    </div>
                    <div className="home-stat-value">{stat.value}</div>
                    <div className="home-stat-label">{stat.label}</div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        <section className="home-products animate-fade-in-up delay-300">
          <div className="home-section-heading home-section-heading--row">
            <div>
              <h2>Produits tendance</h2>
            </div>
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

        {/* Testimonials Section */}
        <section className="home-testimonials-section animate-fade-in-up delay-400" aria-label="Témoignages clients">
          <div className="home-testimonials-container">
            <div className="home-section-heading text-center mx-auto">
              <h2>Avis de nos clients</h2>
              <p className="mt-2 text-stone-600">Découvrez les retours d&apos;expérience de ceux qui nous font confiance.</p>
            </div>
            <div className="home-testimonials-grid">
              {clientReviews.map((review) => (
                <div key={review.name} className="home-testimonial-card">
                  <div className="home-testimonial-stars">
                    {[...Array(review.rating)].map((_, i) => (
                      <Star key={i} className="home-testimonial-star-filled" aria-hidden="true" />
                    ))}
                  </div>
                  <blockquote className="home-testimonial-comment">
                    &ldquo;{review.comment}&rdquo;
                  </blockquote>
                  <div className="home-testimonial-footer">
                    <cite className="home-testimonial-author">{review.name}</cite>
                    <span className="home-testimonial-role">{review.role}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      </div>
    </SiteLayout>
  );
};
