export const adminVoucherQueryKeys = {
  list: () => ['admin', 'vouchers'] as const,
  detail: (id: number | null) => ['admin', 'vouchers', 'detail', id] as const,
};
