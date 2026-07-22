import { useEffect, useMemo, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Search } from 'lucide-react';

import { searchPublicProducts, type CatalogProduct } from '@/features/catalog/api';
import { getCatalogProductDisplayName } from '@/features/catalog/utils/productDisplay';
import { fetchPublicQuoteServices, type QuoteServiceDto } from '@/features/quotes/api';
import {
  fetchPublicTrainings,
  formatTrainingCategory,
  formatTrainingFormat,
  type TrainingDto,
} from '@/features/trainings/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { SITE_URL } from '@/shared/config/seoConfig';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { formatEuroCents } from '@/shared/lib/formatters';

const RESULTS_LIMIT = 6;

const formatDuration = (minutes: number) => {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;

  return hours > 0 ? `${hours}h${rest ? String(rest).padStart(2, '0') : ''}` : `${minutes} min`;
};

const normalize = (value: string | null | undefined) =>
  (value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const matchesSearch = (query: string, fields: Array<string | null | undefined>) => {
  const normalizedQuery = normalize(query);

  if (!normalizedQuery) {
    return true;
  }

  return fields.some((field) => normalize(field).includes(normalizedQuery));
};

const ResultSection = ({
  title,
  count,
  viewAllTo,
  children,
}: {
  title: string;
  count: number;
  viewAllTo: string;
  children: ReactNode;
}) => (
  <section className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm" aria-labelledby={`${title}-title`}>
    <div className="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p className="text-sm font-medium text-stone-500">{count} résultat{count > 1 ? 's' : ''}</p>
        <h2 id={`${title}-title`} className="text-2xl font-semibold text-brand-900">
          {title}
        </h2>
      </div>
      <Link to={viewAllTo} className="text-sm font-semibold text-brand-700 hover:text-brand-900">
        Voir tout
      </Link>
    </div>
    {children}
  </section>
);

export const GlobalSearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const query = searchParams.get('q')?.trim() ?? '';
  const [draftQuery, setDraftQuery] = useState(query);
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [productTotal, setProductTotal] = useState(0);
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [serviceTotal, setServiceTotal] = useState(0);
  const [trainings, setTrainings] = useState<TrainingDto[]>([]);
  const [trainingTotal, setTrainingTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useDocumentTitle(query ? `Recherche : ${query}` : 'Recherche');
  useMetaTags({
    title: query ? `Recherche : ${query} — Hociatec` : 'Recherche — Hociatec',
    description: 'Recherchez rapidement un produit, un service ou une formation Hociatec.',
    canonicalUrl: `${SITE_URL}/recherche`,
  });

  useEffect(() => {
    setDraftQuery(query);
  }, [query]);

  useEffect(() => {
    setError(null);
    setLoading(true);

    void Promise.all([
      searchPublicProducts({
        q: query || undefined,
        page: 1,
        perPage: RESULTS_LIMIT,
        sort: query ? 'relevance' : 'created_desc',
      }),
      fetchPublicQuoteServices(),
      fetchPublicTrainings(),
    ])
      .then(([productResult, serviceItems, trainingItems]) => {
        const matchingServices = serviceItems.filter((service) =>
          matchesSearch(query, [service.title, service.description, service.unit, service.durationLabel]),
        );
        const matchingTrainings = trainingItems.filter((training) =>
          matchesSearch(query, [
            training.title,
            training.shortDescription,
            training.objective,
            training.audience,
            formatTrainingCategory(training.category),
          ]),
        );

        setProducts(productResult.items);
        setProductTotal(productResult.meta.total);
        setServices(matchingServices.slice(0, RESULTS_LIMIT));
        setServiceTotal(matchingServices.length);
        setTrainings(matchingTrainings.slice(0, RESULTS_LIMIT));
        setTrainingTotal(matchingTrainings.length);
      })
      .catch((err: Error) => setError(err.message || 'Impossible de charger les résultats.'))
      .finally(() => setLoading(false));
  }, [query]);

  const resultsTotal = productTotal + serviceTotal + trainingTotal;
  const fullProductSearchUrl = useMemo(
    () => (query ? `/catalogue/recherche?q=${encodeURIComponent(query)}` : '/catalogue/recherche'),
    [query],
  );
  const fullTrainingSearchUrl = useMemo(
    () => (query ? `/formations?q=${encodeURIComponent(query)}` : '/formations'),
    [query],
  );

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const next = draftQuery.trim();
    const params = new URLSearchParams();

    if (next) {
      params.set('q', next);
    }

    setSearchParams(params);
  };

  return (
    <SiteLayout headerVariant="light">
      <main className="public-directory-page mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        <header className="public-directory-page__hero rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
          <span className="inline-flex w-fit rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
            Recherche Hociatec
          </span>
          <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">
            Trouver un produit, un service ou une formation
          </h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
            Lancez une recherche globale, puis ouvrez la fiche qui correspond à votre besoin.
          </p>
          <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-3 sm:flex-row" role="search">
            <label htmlFor="global-search" className="sr-only">
              Rechercher sur tout le site
            </label>
            <div className="relative flex-1">
              <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400" aria-hidden="true" />
              <input
                id="global-search"
                type="search"
                value={draftQuery}
                onChange={(event) => setDraftQuery(event.target.value)}
                placeholder="Exemple : ordinateur, audit, sécurité..."
                className="w-full rounded-full border border-brand-200 py-3 pl-12 pr-4 text-base text-brand-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
              />
            </div>
            <button type="submit" className="rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800">
              Rechercher
            </button>
          </form>
          <p className="mt-4 text-sm text-stone-500" aria-live="polite">
            {query ? `${resultsTotal} résultat${resultsTotal > 1 ? 's' : ''} pour "${query}"` : 'Saisissez un mot-clé pour cibler les résultats.'}
          </p>
        </header>

        {loading ? (
          <LoadingState>Recherche en cours...</LoadingState>
        ) : error ? (
          <ErrorState>{error}</ErrorState>
        ) : (
          <div className="grid gap-6">
            <ResultSection title="Produits" count={productTotal} viewAllTo={fullProductSearchUrl}>
              {products.length === 0 ? (
                <p className="mt-5 text-sm text-stone-500">Aucun produit trouvé.</p>
              ) : (
                <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                  {products.map((product) => (
                    <Link
                      key={product.id}
                      to={`/catalogue/produits/${product.slug}`}
                      className="flex h-full flex-col rounded-xl border border-brand-100 bg-brand-50 p-4 transition hover:border-brand-300 hover:bg-white"
                    >
                      <span className="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
                        {product.sellingType === 'rental' ? 'Location' : 'Vente'} · {product.category.name}
                      </span>
                      <strong className="mt-3 text-base text-brand-900">{getCatalogProductDisplayName(product)}</strong>
                      {product.shortDescription ? (
                        <span className="mt-2 line-clamp-2 text-sm leading-6 text-stone-600">{product.shortDescription}</span>
                      ) : null}
                      <span className="mt-auto pt-4 text-sm font-semibold text-brand-900">
                        {formatEuroCents(product.priceCents)}{product.sellingType === 'rental' ? ' / mois' : ''}
                      </span>
                    </Link>
                  ))}
                </div>
              )}
            </ResultSection>

            <ResultSection title="Services" count={serviceTotal} viewAllTo="/services">
              {services.length === 0 ? (
                <p className="mt-5 text-sm text-stone-500">Aucun service trouvé.</p>
              ) : (
                <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                  {services.map((service) => (
                    <Link
                      key={service.id}
                      to={`/services/${service.id}`}
                      className="flex h-full flex-col rounded-xl border border-brand-100 bg-brand-50 p-4 transition hover:border-brand-300 hover:bg-white"
                    >
                      <span className="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
                        {service.durationLabel || 'Sur étude'}
                      </span>
                      <strong className="mt-3 text-base text-brand-900">{service.title}</strong>
                      {service.description ? (
                        <span className="mt-2 line-clamp-2 text-sm leading-6 text-stone-600">{service.description}</span>
                      ) : null}
                      <span className="mt-auto pt-4 text-sm font-semibold text-brand-900">
                        {formatEuroCents(service.priceCents)}
                      </span>
                    </Link>
                  ))}
                </div>
              )}
            </ResultSection>

            <ResultSection title="Formations" count={trainingTotal} viewAllTo={fullTrainingSearchUrl}>
              {trainings.length === 0 ? (
                <p className="mt-5 text-sm text-stone-500">Aucune formation trouvée.</p>
              ) : (
                <div className="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                  {trainings.map((training) => (
                    <Link
                      key={training.id}
                      to={`/formations/${training.slug}`}
                      className="flex h-full flex-col rounded-xl border border-brand-100 bg-brand-50 p-4 transition hover:border-brand-300 hover:bg-white"
                    >
                      <span className="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
                        {formatTrainingCategory(training.category)} · {formatDuration(training.durationMinutes)}
                      </span>
                      <strong className="mt-3 text-base text-brand-900">{training.title}</strong>
                      {training.shortDescription ? (
                        <span className="mt-2 line-clamp-2 text-sm leading-6 text-stone-600">{training.shortDescription}</span>
                      ) : null}
                      <span className="mt-auto pt-4 text-sm font-semibold text-brand-900">
                        {formatEuroCents(training.priceCents)} · {training.availableFormats.map(formatTrainingFormat).join(', ')}
                      </span>
                    </Link>
                  ))}
                </div>
              )}
            </ResultSection>
          </div>
        )}
      </main>
    </SiteLayout>
  );
};
