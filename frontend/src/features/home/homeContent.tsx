import { Link } from 'react-router';
import { ArrowRight, Clock3 } from 'lucide-react';

import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import type { NewsArticleDto } from '@/features/news/publicApi';
import { formatServiceBillingMode } from '@/features/quotes/publicApi';
import { resolveServiceIllustration } from '@/features/quotes/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';

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

export const HomeProductsHeading = () => (
  <div className="home-section-heading">
    <h2>Produits recommandés</h2>
  </div>
);

export const HomeServicesHeading = () => (
  <div className="home-section-heading">
    <h2>Services mis en avant</h2>
  </div>
);

export const HomeNewsHeading = () => (
  <div className="home-section-heading home-section-heading--row">
    <div>
      <h2>Actualité</h2>
    </div>
    <Link to="/actualites" className="home-button home-button--secondary">
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
      <Link to={`/actualites/${article.slug}`}>{article.title}</Link>
    </h3>
    <p>{article.excerpt}</p>
    <Link to={`/actualites/${article.slug}`} className="home-news-card__link">
      Lire l'actualité
      <ArrowRight aria-hidden="true" />
    </Link>
  </article>
);

export const HomeFeaturedServiceCard = ({ service }: { service: QuoteServiceDto }) => {
  const illustration = resolveServiceIllustration(service);

  return (
    <article className="home-service-card home-service-card--featured">
      <Link to={`/services/${service.id}`} className="home-service-card__media">
        {illustration ? (
          <img
            src={illustration.imageUrl}
            alt={illustration.imageAlt || service.title}
            loading="lazy"
            decoding="async"
          />
        ) : (
          <div className="home-service-card__media-fallback" aria-hidden="true" />
        )}
      </Link>
      <div className="home-service-card__body">
        <h3 className="home-service-card__title">
          <Link to={`/services/${service.id}`}>{service.title}</Link>
        </h3>
        <dl className="home-service-card__facts">
          <div>
            <dt>Mode de facturation</dt>
            <dd>{formatServiceBillingMode(service.unit)}</dd>
          </div>
          <div>
            <dt>Prix HT</dt>
            <dd>{formatEuroCents(service.priceCents)}</dd>
          </div>
          <div>
            <dt>Durée</dt>
            <dd>
              <Clock3 aria-hidden="true" />
              <span>{service.durationLabel || 'Sur étude'}</span>
            </dd>
          </div>
        </dl>
        <p className="home-service-card__description">{getFirstSentence(service.description)}</p>
      </div>
    </article>
  );
};
