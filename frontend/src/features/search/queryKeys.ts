export const searchQueryKeys = {
  global: (query: string, limit: number) => ['search', 'global', { query, limit }] as const,
};
