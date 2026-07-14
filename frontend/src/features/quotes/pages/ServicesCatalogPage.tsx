import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { fetchPublicQuoteServices } from '@/features/quotes/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { SITE_URL } from '@/shared/config/seoConfig';

type PublicService = {
  id: number;
  title: string;
  description?: string | null;
  unit?: string | null;
  durationValue?: number | null;
  durationUnit?: 'hour' | 'day' | null;
  durationLabel?: string | null;
  priceCents: number;
  vatRate?: number;
};

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(cents / 100);

const SERVICES_PER_PAGE = 7;

export const ServicesCatalogPage = () => {
  useDocumentTitle('Services');

  const [services, setServices] = useState<PublicService[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  useMetaTags({
    title: 'Services — Hociatec',
    description: 'Découvrez le catalogue de services Hociatec avec les détails, la durée estimée et la base tarifaire de chaque offre.',
    canonicalUrl: `${SITE_URL}/services`,
  });

  useEffect(() => {
    setLoading(true);
    setError(null);

    void fetchPublicQuoteServices()
      .then((items) => {
        setServices(items);
        setPage(1);
      })
      .catch((err: Error) => setError(err.message || 'Impossible de charger les services.'))
      .finally(() => setLoading(false));
  }, []);

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
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 py-12">
        <header className="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
          <span className="inline-flex w-fit rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-800">
            Services Hociatec
          </span>
          <h1 className="mt-4 text-4xl font-semibold tracking-tight text-slate-950">Catalogue de services</h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-slate-600">
            Retrouvez l’ensemble des services proposés par Hociatec avec leurs informations essentielles,
            leur durée estimée et leur base tarifaire.
          </p>
        </header>

        <section className="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
          {loading ? (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-slate-600">
              Chargement des services...
            </div>
          ) : error ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-10 text-center text-red-700">
              {error}
            </div>
          ) : services.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-slate-600">
              Aucun service n’est publié pour le moment.
            </div>
          ) : (
            <div className="space-y-8">
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {paginatedServices.map((service) => (
                  <article key={service.id} className="flex h-full flex-col rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <div className="space-y-3">
                      <div className="flex items-start justify-between gap-3">
                        <h3 className="text-lg font-semibold text-slate-950">{service.title}</h3>
                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                          {service.unit?.trim() || 'Prix fixe'}
                        </span>
                      </div>
                      <p className="min-h-[4.5rem] text-sm leading-6 text-slate-600">
                        {service.description?.trim() || 'Plus de détails disponibles dans la fiche du service.'}
                      </p>
                    </div>
                    <div className="mt-6 grid gap-3 border-t border-slate-200 pt-4 text-sm text-slate-600">
                      <div className="flex items-center justify-between gap-4">
                        <span>Durée estimée</span>
                        <strong className="text-slate-900">{service.durationLabel || 'Sur étude'}</strong>
                      </div>
                      <div className="flex items-end justify-between gap-4">
                        <div>
                          <p className="text-xs uppercase tracking-[0.16em] text-slate-500">Base tarifaire</p>
                          <p className="mt-1 text-xl font-semibold text-slate-950">{formatPrice(service.priceCents)}</p>
                        </div>
                        <Link
                          to={`/services/${service.id}`}
                          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                          Voir le détail
                        </Link>
                      </div>
                    </div>
                  </article>
                ))}
              </div>

              {totalPages > 1 && (
                <nav className="flex flex-wrap items-center justify-center gap-3" aria-label="Pagination des services">
                  <button
                    type="button"
                    className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 disabled:cursor-not-allowed disabled:opacity-50"
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
                              ? 'bg-slate-900 text-white'
                              : 'border border-slate-300 text-slate-700 hover:border-slate-400'
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
                    className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 disabled:cursor-not-allowed disabled:opacity-50"
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
