export const adminOrderQueryKeys = {
  metadata: () => ['admin', 'orders', 'metadata'] as const,
  list: (status: string, health: string, search: string, sort: string) =>
    ['admin', 'orders', { status, health, search, sort }] as const,
  detail: (id: number | null) => ['admin', 'orders', 'detail', id] as const,
};
