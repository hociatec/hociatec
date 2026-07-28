import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { useSearchParams } from 'react-router';

import { GlobalSearchHeader } from '@/features/search/components/GlobalSearchHeader';
import {
  EmptySearchResults,
  NewsSearchResults,
  ProductSearchResults,
  SearchResultSection,
  ServiceSearchResults,
  TrainingSearchResults,
} from '@/features/search/components/GlobalSearchResults';
import { useGlobalSearch } from '@/features/search/hooks/useGlobalSearch';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

const RESULTS_LIMIT = 6;

interface SearchSectionProps {
  title: string;
  count: number;
  viewAllTo: string;
  emptyLabel: string;
  resultCount: number;
  children: ReactNode;
}

const SearchSection = ({
  title,
  count,
  viewAllTo,
  emptyLabel,
  resultCount,
  children,
}: SearchSectionProps) => (
  <SearchResultSection title={title} count={count} viewAllTo={viewAllTo}>
    {resultCount === 0 ? <EmptySearchResults label={emptyLabel} /> : children}
  </SearchResultSection>
);

export const GlobalSearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const query = searchParams.get('q')?.trim() ?? '';
  const filter = searchParams.get('type') ?? 'all';
  const [draftQuery, setDraftQuery] = useState(query);
  const search = useGlobalSearch(query, RESULTS_LIMIT);

  useDocumentTitle(query ? `Recherche : ${query}` : 'Recherche');
  useMetaTags({
    title: query ? `Recherche : ${query} — Hociatec` : 'Recherche — Hociatec',
    description: 'Recherchez rapidement un produit, un service ou une formation Hociatec.',
    canonicalUrl: `${SITE_URL}/recherche`,
  });

  useEffect(() => setDraftQuery(query), [query]);

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const nextQuery = draftQuery.trim();
    setSearchParams({
      ...(nextQuery ? { q: nextQuery } : {}),
      ...(filter !== 'all' ? { type: filter } : {}),
    });
  };

  const handleFilterChange = (nextFilter: string) => {
    setSearchParams({
      ...(query ? { q: query } : {}),
      ...(nextFilter !== 'all' ? { type: nextFilter } : {}),
    });
  };

  const sectionVisible = (type: string, count: number) => (filter === 'all' || filter === type) && count > 0;
  const resultsTotal =
    (filter === 'all' || filter === 'products' ? search.productTotal : 0) +
    (filter === 'all' || filter === 'services' ? search.serviceTotal : 0) +
    (filter === 'all' || filter === 'trainings' ? search.trainingTotal : 0) +
    (filter === 'all' || filter === 'news' ? search.newsTotal : 0);
  const productSearchUrl = query
    ? `/catalogue/recherche?q=${encodeURIComponent(query)}`
    : '/catalogue/recherche';
  const trainingSearchUrl = query ? `/formations?q=${encodeURIComponent(query)}` : '/formations';
  const newsSearchUrl = query ? `/actualites?q=${encodeURIComponent(query)}` : '/actualites';

  return (
    <SiteLayout headerVariant="light">
      <main className="public-directory-page mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        <GlobalSearchHeader
          query={query}
          draftQuery={draftQuery}
          filter={filter}
          resultsTotal={resultsTotal}
          onDraftQueryChange={setDraftQuery}
          onFilterChange={handleFilterChange}
          onSubmit={handleSubmit}
        />
        {search.loading ? (
          <LoadingState>Recherche en cours...</LoadingState>
        ) : search.error ? (
          <ErrorState>{search.error}</ErrorState>
        ) : (
          <div className="grid gap-6">
            {resultsTotal === 0 ? (
              <EmptySearchResults label="Aucun résultat trouvé pour cette recherche." />
            ) : null}
            {sectionVisible('products', search.productTotal) ? (
              <SearchSection
                title="Produits"
                count={search.productTotal}
                viewAllTo={productSearchUrl}
                emptyLabel=""
                resultCount={search.products.length}
              >
                <ProductSearchResults products={search.products} />
              </SearchSection>
            ) : null}
            {sectionVisible('services', search.serviceTotal) ? (
              <SearchSection
                title="Services"
                count={search.serviceTotal}
                viewAllTo="/services"
                emptyLabel=""
                resultCount={search.services.length}
              >
                <ServiceSearchResults services={search.services} />
              </SearchSection>
            ) : null}
            {sectionVisible('trainings', search.trainingTotal) ? (
              <SearchSection
                title="Formations"
                count={search.trainingTotal}
                viewAllTo={trainingSearchUrl}
                emptyLabel=""
                resultCount={search.trainings.length}
              >
                <TrainingSearchResults trainings={search.trainings} />
              </SearchSection>
            ) : null}
            {sectionVisible('news', search.newsTotal) ? (
              <SearchSection
                title="Actualités"
                count={search.newsTotal}
                viewAllTo={newsSearchUrl}
                emptyLabel=""
                resultCount={search.news.length}
              >
                <NewsSearchResults news={search.news} />
              </SearchSection>
            ) : null}
          </div>
        )}
      </main>
    </SiteLayout>
  );
};
