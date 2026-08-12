export const trainingQueryKeys = {
  publicCatalog: () => ['trainings', 'public-catalog'] as const,
  publicCatalogSearch: (params: Record<string, unknown>) =>
    ['trainings', 'public-catalog-search', params] as const,
  publicDetail: (slug: string) => ['trainings', 'public-detail', slug] as const,
  myEnrollments: () => ['trainings', 'my-enrollments'] as const,
};
