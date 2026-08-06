export const adminCustomerQueryKeys = {
  list: (search: string, sort: string) => ['admin', 'customers', { search, sort }] as const,
  detail: (id: number | null) => ['admin', 'customers', 'detail', id] as const,
  vouchers: (id: number | null) => ['admin', 'customers', 'vouchers', id] as const,
};
