import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router';

import { fetchNewsArticle } from '@/features/news/api/newsApi';
import { NewsComments } from '@/features/news/components/NewsComments';
import { NewsShareActions } from '@/features/news/components/NewsShareActions';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { newsQueryKeys } from '@/features/news/queryKeys';

const formatDate = (value: string | null) => (value ? formatOptionalFrenchDate(value) : 'Date non définie');

export const normalizeSlugFromUrl = (value: string) => {
  const decoded = (() => {
    try {
      return decodeURIComponent(value);
    } catch {
      return value;
    }
  })();

  return decoded.trim().replace(/^["'«»“”]+|["'«»“”.:,;!?]+$/g, '');
};

export const NewsDetailPage = () => {
  const { slug: rawSlug = '' } = useParams();
  const slug = normalizeSlugFromUrl(rawSlug);
  const articleQuery = useQuery({
    queryKey: newsQueryKeys.article(slug),
    queryFn: ({ signal }) => fetchNewsArticle(slug, { signal }),
    enabled: slug.length > 0,
  });
  const article = articleQuery.data ?? null;
  const error = articleQuery.error instanceof Error ? articleQuery.error.message : null;

  useDocumentTitle(article ? article.title : 'Actualité');
  useMetaTags({
    title: article ? `${article.title} — Hociatec` : 'Actualité — Hociatec',
    description: article?.excerpt ?? 'Actualité Hociatec.',
    canonicalUrl: `${SITE_URL}/actualites/${slug}`,
  });

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-4xl flex-col gap-8 px-6 py-12">
        <Link to="/actualites" className="text-sm font-semibold text-brand-700 underline">
          Retour aux actualités
        </Link>

        {articleQuery.isLoading ? (
          <LoadingState>Chargement de l’actualité...</LoadingState>
        ) : error ? (
          <ErrorState onAction={() => void articleQuery.refetch()}>{error}</ErrorState>
        ) : article ? (
          <>
            <article className="rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
              <p className="text-sm font-semibold text-stone-500">
                Date de publication : {formatDate(article.publishedAt)}
              </p>
              <h1 className="mt-5 text-4xl font-semibold tracking-tight text-brand-900">
                {article.title}
              </h1>
              <div className="mt-5">
                <NewsShareActions article={article} />
              </div>
              <p className="mt-5 text-lg leading-8 text-stone-600">{article.excerpt}</p>
              <div className="mt-8 space-y-5 whitespace-pre-line text-base leading-8 text-stone-700">
                {article.content}
              </div>
            </article>
            <NewsComments slug={article.slug} />
          </>
        ) : null}
      </main>
    </SiteLayout>
  );
};
