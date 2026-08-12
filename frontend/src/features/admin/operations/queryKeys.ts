export const adminOperationsQueryKeys = {
  base: () => ['admin', 'operations'] as const,
  overview: () => ['admin', 'operations', 'overview'] as const,
  support: (page: number) => ['admin', 'operations', 'support', { page }] as const,
  refunds: (page: number) => ['admin', 'operations', 'refunds', { page }] as const,
  stock: (page: number) => ['admin', 'operations', 'stock', { page }] as const,
  emails: (page: number) => ['admin', 'operations', 'emails', { page }] as const,
  fulfillment: (page: number) => ['admin', 'operations', 'fulfillment', { page }] as const,
};
