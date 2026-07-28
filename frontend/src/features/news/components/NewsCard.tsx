import { Link } from 'react-router';

import type { NewsArticleDto } from '@/features/news/api/newsApi';

const formatDate = (value: string | null) =>
  value
    ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(
        new Date(value),
      )
    : 'Date non définie';

export const NewsCard = ({ article }: { article: NewsArticleDto }) => (
  <article className="flex h-full flex-col rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
    <p className="text-sm font-semibold text-stone-500">
      Date de publication : {formatDate(article.publishedAt)}
    </p>
    {article.category ? (
      <p className="mt-2 w-fit rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">
        {article.category}
      </p>
    ) : null}
    <h2 className="mt-4 text-2xl font-semibold text-brand-900">
      <Link to={`/actualites/${article.slug}`} className="hover:text-brand-700">
        {article.title}
      </Link>
    </h2>
    <p className="mt-3 line-clamp-4 text-sm leading-6 text-stone-600">{article.excerpt}</p>
    <Link
      to={`/actualites/${article.slug}`}
      className="mt-auto pt-5 text-sm font-semibold text-brand-700 hover:text-brand-900"
    >
      Lire l’actualité
    </Link>
  </article>
);
