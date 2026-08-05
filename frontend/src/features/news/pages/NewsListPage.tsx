import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';

import { fetchNewsArticles, type NewsArticleDto, type PaginationMeta } from '@/features/news/api/newsApi';
import { NewsCard } from '@/features/news/components/NewsCard';
import { NewsPagination } from '@/features/news/components/NewsPagination';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';

export const NewsListPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = Math.max(1, Number(searchParams.get('page') ?? 1) || 1);
  const q = searchParams.get('q')?.trim() ?? '';
  const [items, setItems] = useState<NewsArticleDto[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useDocumentTitle('Actualités');
  useMetaTags({
    title: 'Actualités — Hociatec',
    description: 'Retrouvez les actualités Hociatec, les nouveautés de service et les annonces importantes.',
    canonicalUrl: `${SITE_URL}/actualites`,
  });

  useEffect(() => {
    let cancelled = false;
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    void fetchNewsArticles({ page, q, signal: controller.signal })
      .then((result) => {
        if (cancelled) return;
        setItems(result.items);
        setMeta(result.meta);
      })
      .catch((reason) => {
        if (controller.signal.aborted) return;
        if (!cancelled) setError(reason instanceof Error ? reason.message : 'Erreur de chargement.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
      controller.abort();
    };
  }, [page, q]);

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
            Actualités Hociatec
          </h1>
          <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
            Suivez les annonces, les évolutions de service et les informations utiles autour de l’accompagnement numérique Hociatec.
          </p>
        </header>

        {loading ? (
          <LoadingState>Chargement des actualités...</LoadingState>
        ) : error ? (
          <ErrorState>{error}</ErrorState>
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
