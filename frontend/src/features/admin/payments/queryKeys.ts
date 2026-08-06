export const adminPaymentQueryKeys = {
  metadata: () => ['admin', 'payments', 'metadata'] as const,
  list: (status: string, search: string) => ['admin', 'payments', { status, search }] as const,
  detail: (id: number | null) => ['admin', 'payments', 'detail', id] as const,
};
