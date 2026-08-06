export const adminLoyaltyQueryKeys = {
  customers: (search: string) => ['admin', 'loyalty', 'customers', { search }] as const,
};
