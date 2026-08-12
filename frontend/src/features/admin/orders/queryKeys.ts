export const adminOrderQueryKeys = {
  metadata: () => ['admin', 'orders', 'metadata'] as const,
  list: (status: string, health: string, q: string, sort: string) =>
    ['admin', 'orders', { status, health, q, sort }] as const,
  detail: (id: number | null) => ['admin', 'orders', 'detail', id] as const,
};
