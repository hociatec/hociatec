export const trainingQueryKeys = {
  publicCatalog: () => ['trainings', 'public-catalog'] as const,
  publicDetail: (slug: string) => ['trainings', 'public-detail', slug] as const,
  myEnrollments: () => ['trainings', 'my-enrollments'] as const,
};
