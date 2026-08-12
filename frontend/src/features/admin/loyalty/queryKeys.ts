export const adminLoyaltyQueryKeys = {
  customers: (q: string) => ['admin', 'loyalty', 'customers', { q }] as const,
};
