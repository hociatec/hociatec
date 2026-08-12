import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import { HomeFeaturedServiceCard } from '@/features/home/publicApi';
import { searchPublicQuoteServices } from '@/features/quotes/publicApi';
import { quoteQueryKeys } from '@/features/quotes/queryKeys';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { omitUndefinedProperties } from '@/shared/lib/object';
import '@/app/styles/features/directories.css';

const SERVICES_PER_PAGE = 7;

export const ServicesCatalogPage = () => {
  useDocumentTitle('Services');
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Math.max(1, Number(searchParams.get('page') ?? '1') || 1);
  const query = searchParams.get('q')?.trim() ?? '';

  const servicesQuery = useQuery({
    queryKey: quoteQueryKeys.publicServicesSearch(query, page, SERVICES_PER_PAGE),
    queryFn: ({ signal }) =>
      searchPublicQuoteServices(omitUndefinedProperties({
        page,
        perPage: SERVICES_PER_PAGE,
        q: query || undefined,
        signal,
      })),
  });

  const services = servicesQuery.data?.items ?? [];
  const pagination = servicesQuery.data?.meta ?? {
    page,
    perPage: SERVICES_PER_PAGE,
    total: 0,
    totalPages: 1,
  };

  useMetaTags({
    title: 'Services — Hociatec',
    description:
      'Découvrez le catalogue de services Hociatec avec les détails, la durée estimée et la base tarifaire de chaque offre.',
    canonicalUrl: `${SITE_URL}/services`,
  });

  const updateParams = (patch: Record<string, string | null>) => {
    const next = new URLSearchParams(searchParams);
    Object.entries(patch).forEach(([key, value]) => {
      if (!value) {
        next.delete(key);
        return;
      }
      next.set(key, value);
    });
    if (!('page' in patch)) {
      next.delete('page');
    }
    setSearchParams(next, { replace: true });
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        title="Nos services"
        description="Retrouvez l’ensemble des services proposés par Hociatec avec leurs informations essentielles, leur durée estimée et leur base tarifaire."
      >
        <PublicPageSection className="public-directory-page__panel p-8">
          <div className="mb-6 max-w-md">
            <SearchFilter
              value={query}
              onChange={(value) => updateParams({ q: value || null, page: null })}
              placeholder="Rechercher un service..."
            />
          </div>

          {servicesQuery.isLoading ? (
            <LoadingState>Chargement des services...</LoadingState>
          ) : servicesQuery.error ? (
            <ErrorState onAction={() => void servicesQuery.refetch()}>
              {servicesQuery.error.message}
            </ErrorState>
          ) : pagination.total === 0 ? (
            <EmptyState>
              {query
                ? 'Aucun service ne correspond à cette recherche.'
                : 'Aucun service n’est publié pour le moment.'}
            </EmptyState>
          ) : (
            <div className="space-y-8">
              <div className="home-products__grid home-products__grid--services">
                {services.map((service) => (
                  <HomeFeaturedServiceCard key={service.id} service={service} />
                ))}
              </div>

              {pagination.totalPages > 1 && (
                <nav
                  className="flex flex-wrap items-center justify-center gap-3"
                  aria-label="Pagination des services"
                >
                  <button
                    type="button"
                    className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={page <= 1}
                    onClick={() => updateParams({ page: String(page - 1) })}
                  >
                    Précédent
                  </button>

                  <div className="flex flex-wrap items-center justify-center gap-2">
                    {Array.from({ length: pagination.totalPages }, (_, index) => {
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
                          onClick={() => updateParams({ page: String(pageNumber) })}
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
                    disabled={page >= pagination.totalPages}
                    onClick={() => updateParams({ page: String(page + 1) })}
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
