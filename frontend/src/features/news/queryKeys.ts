export const newsQueryKeys = {
  articles: () => ['news', 'articles'] as const,
  articlesPage: (page: number, q: string) => [...newsQueryKeys.articles(), { page, q }] as const,
  article: (slug: string) => ['news', 'article', slug] as const,
  comments: (slug: string) => ['news', 'comments', slug] as const,
  commentsPage: (slug: string, page: number) => [...newsQueryKeys.comments(slug), { page }] as const,
};
