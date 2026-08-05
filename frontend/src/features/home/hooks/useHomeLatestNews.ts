import { useQuery } from '@tanstack/react-query';

import { fetchNewsArticles, type NewsArticleDto } from '@/features/news/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { homeQueryKeys } from '@/shared/lib/queryKeys';

const HOMEPAGE_NEWS_LIMIT = 3;

export const useHomeLatestNews = () => {
  const query = useQuery<NewsArticleDto[], Error>({
    queryKey: homeQueryKeys.latestNews(),
    queryFn: async ({ signal }) => {
      const { items } = await fetchNewsArticles({
        page: 1,
        perPage: HOMEPAGE_NEWS_LIMIT,
        signal,
      });

      return items.slice(0, HOMEPAGE_NEWS_LIMIT);
    },
  });

  return {
    articles: query.data ?? [],
    loading: query.isLoading,
    error: query.error
      ? getHttpErrorMessage(query.error, 'Impossible de charger les actualités.')
      : null,
  };
};
