import { Link } from 'react-router';

import type { NewsArticleDto } from '@/features/news/api/newsApi';
import { NewsShareActions } from '@/features/news/components/NewsShareActions';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

const formatDate = (value: string | null) =>
  value ? formatOptionalFrenchDate(value) : 'Date non définie';

export const NewsCard = ({ article }: { article: NewsArticleDto }) => (
  <article className="flex h-full flex-col rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
    <p className="text-sm font-semibold text-stone-500">
      Date de publication : {formatDate(article.publishedAt)}
    </p>
    <h2 className="mt-4 text-2xl font-semibold text-brand-900">
      <Link to={`/actualites/${article.slug}`} className="hover:text-brand-700">
        {article.title}
      </Link>
    </h2>
    <p className="mt-3 line-clamp-4 text-sm leading-6 text-stone-600">{article.excerpt}</p>
    <div className="mt-auto flex flex-col gap-4 pt-5">
      <Link
        to={`/actualites/${article.slug}`}
        className="text-sm font-semibold text-brand-700 hover:text-brand-900"
      >
        Lire l’actualité
      </Link>
      <NewsShareActions article={article} compact />
    </div>
  </article>
);
