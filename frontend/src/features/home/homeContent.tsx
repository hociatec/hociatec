import { Link } from 'react-router';
import { ArrowRight } from 'lucide-react';

import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { resolvePublicAssetUrl } from '@/shared/lib/publicAssetUrl';
import type { NewsArticleDto } from '@/features/news/publicApi';
import { formatServiceBillingMode } from '@/features/quotes/publicApi';
import { resolveServiceIllustration } from '@/features/quotes/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import '@/app/styles/home.css';

const formatNewsDate = (value: string | null) =>
  value ? formatOptionalFrenchDate(value) : 'Date non définie';

const getFirstSentence = (value?: string | null) => {
  const description = value?.trim();
  if (!description) {
    return 'Plus de détails disponibles dans la fiche du service.';
  }

  const [sentence] = description.match(/[^.!?]+[.!?]?/) ?? [description];

  return sentence.trim();
};

const SERVICE_ILLUSTRATION_FALLBACK = resolvePublicAssetUrl('/service-illustrations/service-generique.svg');

export const HomeProductsHeading = () => (
  <div className="home-section-heading home-section-heading--center">
    <h2>Produits recommandés</h2>
    <span>Une sélection courte de matériel utile, lisible et directement actionnable.</span>
  </div>
);

export const HomeServicesHeading = () => (
  <div className="home-section-heading home-section-heading--center">
    <h2>Services mis en avant</h2>
    <span>Des prestations concrètes pour réparer, sécuriser, maintenir ou faire évoluer vos outils.</span>
  </div>
);

export const HomeNewsHeading = () => (
  <div className="home-section-heading home-section-heading--center home-section-heading--news">
    <div>
      <h2>Actualités</h2>
      <span>Les derniers contenus pour suivre les usages, la sécurité et les nouveautés Hociatec.</span>
    </div>
    <Link to="/actualites" prefetch="intent" className="home-button home-button--secondary">
      Toutes les actualités
    </Link>
  </div>
);

export const HomeNewsCard = ({ article }: { article: NewsArticleDto }) => (
  <article className="home-news-card">
    <div className="home-news-card__meta">
      <time dateTime={article.publishedAt || article.createdAt}>
        {formatNewsDate(article.publishedAt)}
      </time>
      {article.category ? <span>{article.category}</span> : null}
    </div>
    <h3>
      <Link to={`/actualites/${article.slug}`} prefetch="intent">
        {article.title}
      </Link>
    </h3>
    <p>{article.excerpt}</p>
    <Link
      to={`/actualites/${article.slug}`}
      prefetch="intent"
      className="home-news-card__link"
    >
      Lire l'actualité
      <ArrowRight aria-hidden="true" />
    </Link>
  </article>
);

export const HomeFeaturedServiceCard = ({ service }: { service: QuoteServiceDto }) => {
  const illustration = resolveServiceIllustration(service);
  const billingMode = formatServiceBillingMode(service.unit);
  const durationLabel = service.durationLabel || 'Sur étude';

  return (
    <article className="home-service-card home-service-card--featured">
      <div className="home-service-card__media">
        {illustration ? (
          <img
            src={illustration.imageUrl}
            alt={illustration.imageAlt || service.title}
            width={400}
            height={260}
            loading="lazy"
            decoding="async"
            onError={(event) => {
              const image = event.currentTarget;
              if (!image.src.endsWith(SERVICE_ILLUSTRATION_FALLBACK)) {
                image.src = SERVICE_ILLUSTRATION_FALLBACK;
              }
            }}
          />
        ) : (
          <div className="home-service-card__media-fallback" aria-hidden="true" />
        )}
      </div>
      <div className="home-service-card__body">
        <h3 className="home-service-card__title">
          <Link to={`/services/${service.id}`} prefetch="intent">
            {service.title}
          </Link>
        </h3>
        <p className="home-service-card__description">{getFirstSentence(service.description)}</p>
        <div className="home-service-card__facts" aria-label={`Informations clés pour ${service.title}`}>
          <p className="home-service-card__fact">
            <span className="home-service-card__fact-label">Mode de facturation:</span> {billingMode}
          </p>
          <p className="home-service-card__fact">
            <span className="home-service-card__fact-label">Prix HT:</span> {formatEuroCents(service.priceCents)}
          </p>
          <p className="home-service-card__fact">
            <span className="home-service-card__fact-label">Durée:</span> {durationLabel}
          </p>
        </div>
      </div>
    </article>
  );
};
