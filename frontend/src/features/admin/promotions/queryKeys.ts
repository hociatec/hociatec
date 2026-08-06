export const adminPromotionQueryKeys = {
  overview: () => ['admin', 'promotions', 'overview'] as const,
  audiences: () => ['admin', 'promotions', 'audiences'] as const,
  detail: (id: number | null) => ['admin', 'promotions', 'detail', id] as const,
};
