export const adminCustomerQueryKeys = {
  list: (q: string, sort: string) => ['admin', 'customers', { q, sort }] as const,
  detail: (id: number | null) => ['admin', 'customers', 'detail', id] as const,
  vouchers: (id: number | null) => ['admin', 'customers', 'vouchers', id] as const,
};
