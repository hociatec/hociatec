import { useEffect, useState } from 'react';

import { fetchNewsArticles, type NewsArticleDto } from '@/features/news/api/newsApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

const HOMEPAGE_NEWS_LIMIT = 3;

export const useHomeLatestNews = () => {
  const [articles, setArticles] = useState<NewsArticleDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    const controller = new AbortController();
    setLoading(true);
    setError(null);

    void fetchNewsArticles({ page: 1, perPage: HOMEPAGE_NEWS_LIMIT, signal: controller.signal })
      .then(({ items }) => {
        if (!cancelled) {
          setArticles(items.slice(0, HOMEPAGE_NEWS_LIMIT));
        }
      })
      .catch((reason) => {
        if (controller.signal.aborted) return;
        if (!cancelled) {
          setError(getHttpErrorMessage(reason, 'Impossible de charger les actualités.'));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
      controller.abort();
    };
  }, []);

  return { articles, loading, error };
};
