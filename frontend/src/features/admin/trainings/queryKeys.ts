export const adminTrainingQueryKeys = {
  trainings: () => ['admin', 'trainings', 'items'] as const,
  training: (id: number | null) => ['admin', 'trainings', 'item', id] as const,
  trainingForm: (id: number | null) => ['admin', 'trainings', 'form', id] as const,
  categories: () => ['admin', 'trainings', 'categories'] as const,
  sessions: () => ['admin', 'trainings', 'sessions'] as const,
  sessionForm: (id: number | null) => ['admin', 'trainings', 'session-form', id] as const,
  enrollments: () => ['admin', 'trainings', 'enrollments'] as const,
  overview: () => ['admin', 'trainings', 'overview'] as const,
};
