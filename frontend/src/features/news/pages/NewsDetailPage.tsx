import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';

import { fetchNewsArticle, type NewsArticleDto } from '@/features/news/api/newsApi';
import { NewsComments } from '@/features/news/components/NewsComments';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { SITE_URL } from '@/shared/config/seoConfig';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

const formatDate = (value: string | null) => (value ? formatOptionalFrenchDate(value) : 'Date non définie');

const normalizeSlugFromUrl = (value: string) =>
  decodeURIComponent(value)
    .trim()
    .replace(/^["'«»“”]+|["'«»“”.:,;!?]+$/g, '');

export const NewsDetailPage = () => {
  const { slug: rawSlug = '' } = useParams();
  const slug = normalizeSlugFromUrl(rawSlug);
  const [article, setArticle] = useState<NewsArticleDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useDocumentTitle(article ? article.title : 'Actualité');
  useMetaTags({
    title: article ? `${article.title} — Hociatec` : 'Actualité — Hociatec',
    description: article?.excerpt ?? 'Actualité Hociatec.',
    canonicalUrl: `${SITE_URL}/actualites/${slug}`,
  });

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    void fetchNewsArticle(slug)
      .then((item) => {
        if (!cancelled) setArticle(item);
      })
      .catch((reason) => {
        if (!cancelled) setError(reason instanceof Error ? reason.message : 'Erreur de chargement.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [slug]);

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-4xl flex-col gap-8 px-6 py-12">
        <Link to="/actualites" className="text-sm font-semibold text-brand-700 underline">
          Retour aux actualités
        </Link>

        {loading ? (
          <LoadingState>Chargement de l’actualité...</LoadingState>
        ) : error ? (
          <ErrorState>{error}</ErrorState>
        ) : article ? (
          <>
            <article className="rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
              <p className="text-sm font-semibold text-stone-500">
                Date de publication : {formatDate(article.publishedAt)}
              </p>
              <h1 className="mt-5 text-4xl font-semibold tracking-tight text-brand-900">
                {article.title}
              </h1>
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
