export const adminNewsQueryKeys = {
  base: () => ['admin', 'news'] as const,
  list: (q: string) => ['admin', 'news', { q }] as const,
  detail: (id: number | null) => ['admin', 'news', 'detail', id] as const,
};
