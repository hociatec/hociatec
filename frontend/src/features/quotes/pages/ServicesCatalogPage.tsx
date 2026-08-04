import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';
import { Clock3 } from 'lucide-react';

import { usePublicQuoteServices } from '@/features/quotes/hooks/usePublicQuoteServices';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatEuroCents } from '@/shared/lib/formatters';
import { resolveServiceIllustration } from '@/features/quotes/lib/servicePresentation';

const SERVICES_PER_PAGE = 7;

const getFirstSentence = (value?: string | null) => {
  const description = value?.trim();
  if (!description) {
    return 'Plus de détails disponibles dans la fiche du service.';
  }

  const [sentence] = description.match(/[^.!?]+[.!?]?/) ?? [description];

  return sentence.trim();
};

export const ServicesCatalogPage = () => {
  useDocumentTitle('Services');

  const { services, loading, error } = usePublicQuoteServices();
  const [page, setPage] = useState(1);

  useMetaTags({
    title: 'Services — Hociatec',
    description:
      'Découvrez le catalogue de services Hociatec avec les détails, la durée estimée et la base tarifaire de chaque offre.',
    canonicalUrl: `${SITE_URL}/services`,
  });

  const totalPages = Math.max(1, Math.ceil(services.length / SERVICES_PER_PAGE));
  const paginatedServices = useMemo(() => {
    const startIndex = (page - 1) * SERVICES_PER_PAGE;
    return services.slice(startIndex, startIndex + SERVICES_PER_PAGE);
  }, [page, services]);

  useEffect(() => {
    if (page > totalPages) {
      setPage(totalPages);
    }
  }, [page, totalPages]);

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        eyebrow="Services Hociatec"
        title="Nos services"
        description="Retrouvez l’ensemble des services proposés par Hociatec avec leurs informations essentielles, leur durée estimée et leur base tarifaire."
      >
        <PublicPageSection className="public-directory-page__panel p-8">
          {loading ? (
            <LoadingState>Chargement des services...</LoadingState>
          ) : error ? (
            <ErrorState>{error}</ErrorState>
          ) : services.length === 0 ? (
            <EmptyState>Aucun service n’est publié pour le moment.</EmptyState>
          ) : (
            <div className="space-y-8">
              <div className="home-products__grid">
                {paginatedServices.map((service) => {
                  const illustration = resolveServiceIllustration(service);

                  return (
                    <article
                      key={service.id}
                      className="home-service-card home-service-card--featured"
                      style={{ contentVisibility: 'auto', containIntrinsicSize: '420px' }}
                    >
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
                            <dd>{service.unit?.trim() || 'Prix fixe'}</dd>
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
                        <p className="home-service-card__description">
                          {getFirstSentence(service.description)}
                        </p>
                      </div>
                    </article>
                  );
                })}
              </div>

              {totalPages > 1 && (
                <nav
                  className="flex flex-wrap items-center justify-center gap-3"
                  aria-label="Pagination des services"
                >
                  <button
                    type="button"
                    className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={page <= 1}
                    onClick={() => setPage((current) => Math.max(1, current - 1))}
                  >
                    Précédent
                  </button>

                  <div className="flex flex-wrap items-center justify-center gap-2">
                    {Array.from({ length: totalPages }, (_, index) => {
                      const pageNumber = index + 1;

                      return (
                        <button
                          key={pageNumber}
                          type="button"
                          className={`h-10 min-w-10 rounded-full px-3 text-sm font-semibold transition ${
                            pageNumber === page
                              ? 'bg-brand-900 text-white'
                              : 'border border-brand-200 text-stone-700 hover:border-brand-300'
                          }`}
                          onClick={() => setPage(pageNumber)}
                          aria-current={pageNumber === page ? 'page' : undefined}
                        >
                          {pageNumber}
                        </button>
                      );
                    })}
                  </div>

                  <button
                    type="button"
                    className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={page >= totalPages}
                    onClick={() => setPage((current) => Math.min(totalPages, current + 1))}
                  >
                    Suivant
                  </button>
                </nav>
              )}
            </div>
          )}
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
