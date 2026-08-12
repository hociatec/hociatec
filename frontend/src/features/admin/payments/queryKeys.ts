export const adminPaymentQueryKeys = {
  metadata: () => ['admin', 'payments', 'metadata'] as const,
  list: (status: string, q: string) => ['admin', 'payments', { status, q }] as const,
  detail: (id: number | null) => ['admin', 'payments', 'detail', id] as const,
};
