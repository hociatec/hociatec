import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router';

import { usePublicQuoteServices } from '@/features/quotes/hooks/usePublicQuoteServices';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatEuroCents } from '@/shared/lib/formatters';

const SERVICES_PER_PAGE = 7;

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
      <div className="public-directory-page mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 py-12">
        <header className="public-directory-page__hero rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
          <span className="inline-flex w-fit rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">
            Services Hociatec
          </span>
          <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">
            Nos services
          </h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
            Retrouvez l’ensemble des services proposés par Hociatec avec leurs informations
            essentielles, leur durée estimée et leur base tarifaire.
          </p>
        </header>

        <section className="public-directory-page__panel rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
          {loading ? (
            <LoadingState>Chargement des services...</LoadingState>
          ) : error ? (
            <ErrorState>{error}</ErrorState>
          ) : services.length === 0 ? (
            <EmptyState>Aucun service n’est publié pour le moment.</EmptyState>
          ) : (
            <div className="space-y-8">
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {paginatedServices.map((service) => (
                  <article
                    key={service.id}
                    className="flex h-full flex-col rounded-xl border border-brand-100 bg-brand-50 p-5"
                  >
                    <div className="space-y-3">
                      <div className="flex items-start justify-between gap-3">
                        <h3 className="text-lg font-semibold text-brand-900">{service.title}</h3>
                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-stone-700 shadow-sm">
                          {service.unit?.trim() || 'Prix fixe'}
                        </span>
                      </div>
                      <p className="min-h-[4.5rem] text-sm leading-6 text-stone-600">
                        {service.description?.trim() ||
                          'Plus de détails disponibles dans la fiche du service.'}
                      </p>
                    </div>
                    <div className="mt-6 grid gap-3 border-t border-brand-100 pt-4 text-sm text-stone-600">
                      <div className="flex items-center justify-between gap-4">
                        <span>Durée estimée</span>
                        <strong className="text-brand-900">
                          {service.durationLabel || 'Sur étude'}
                        </strong>
                      </div>
                      <div className="flex items-end justify-between gap-4">
                        <div>
                          <p className="text-xs uppercase tracking-[0.16em] text-stone-500">
                            Base tarifaire
                          </p>
                          <p className="mt-1 text-xl font-semibold text-brand-900">
                            {formatEuroCents(service.priceCents)}
                          </p>
                        </div>
                        <Link
                          to={`/services/${service.id}`}
                          className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"
                        >
                          Voir le détail
                        </Link>
                      </div>
                    </div>
                  </article>
                ))}
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
        </section>
      </div>
    </SiteLayout>
  );
};
