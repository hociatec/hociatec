import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import { fetchNewsArticles } from '@/features/news/api/newsApi';
import { NewsCard } from '@/features/news/components/NewsCard';
import { NewsPagination } from '@/features/news/components/NewsPagination';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { newsQueryKeys } from '@/features/news/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const NewsListPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = parseNullablePositiveInteger(searchParams.get('page')) ?? 1;
  const q = searchParams.get('q')?.trim() ?? '';
  const articlesQuery = useQuery({
    queryKey: newsQueryKeys.articlesPage(page, q),
    queryFn: ({ signal }) => fetchNewsArticles({ page, q, signal }),
  });
  const items = articlesQuery.data?.items ?? [];
  const meta = articlesQuery.data?.meta ?? null;
  const error = articlesQuery.error instanceof Error ? articlesQuery.error.message : null;

  useDocumentTitle('Actualités');
  useMetaTags({
    title: 'Actualités — Hociatec',
    description: 'Retrouvez les actualités Hociatec, les nouveautés de service et les annonces importantes.',
    canonicalUrl: `${SITE_URL}/actualites`,
  });

  const setPage = (nextPage: number) => {
    const next = new URLSearchParams(searchParams);
    next.set('page', String(nextPage));
    setSearchParams(next);
  };

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        <header className="rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
          <h1 className="text-4xl font-semibold tracking-tight text-brand-900">
            Actualités
          </h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
            Suivez les annonces, les évolutions de service et les informations utiles autour de l’accompagnement numérique Hociatec.
          </p>
        </header>

        {articlesQuery.isLoading ? (
          <LoadingState>Chargement des actualités...</LoadingState>
        ) : error ? (
          <ErrorState onAction={() => void articlesQuery.refetch()}>{error}</ErrorState>
        ) : items.length === 0 ? (
          <p className="rounded-2xl border border-brand-100 bg-white p-6 text-sm text-stone-600">
            Aucune actualité disponible pour le moment.
          </p>
        ) : (
          <>
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              {items.map((article) => (
                <NewsCard key={article.id} article={article} />
              ))}
            </div>
            {meta ? <NewsPagination page={meta.page} totalPages={meta.totalPages} onPageChange={setPage} /> : null}
          </>
        )}
      </main>
    </SiteLayout>
  );
};
