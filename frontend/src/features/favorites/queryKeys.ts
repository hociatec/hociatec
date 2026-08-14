export const favoriteQueryKeys = {
  all: () => ['favorites'] as const,
  lists: () => [...favoriteQueryKeys.all(), 'list'] as const,
  list: (page: number, category: string) => [...favoriteQueryKeys.lists(), { page, category }] as const,
  status: (category: string, targetId: number) => [...favoriteQueryKeys.all(), 'status', category, targetId] as const,
};
