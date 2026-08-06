export const orderQueryKeys = {
  mine: () => ['orders', 'mine'] as const,
  detail: (id: number | null) => ['orders', 'detail', id] as const,
  pendingReviews: () => ['orders', 'pending-reviews'] as const,
  checkoutSession: (sessionId: string | null) => ['orders', 'checkout-session', sessionId] as const,
};
