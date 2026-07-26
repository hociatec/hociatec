import type { ApiResponse } from '@/shared/types/api';

export const TRAINING_API_ROUTES = {
  publicList: '/api/public/trainings',
  publicCategories: '/api/public/training-categories',
  publicDetail: (slug: string) => `/api/public/trainings/${encodeURIComponent(slug)}`,
  enrollments: '/api/trainings/enrollments',
  myEnrollments: '/api/trainings/enrollments/me',
  adminTrainings: '/api/admin/trainings',
  adminTraining: (id: number) => `/api/admin/trainings/${id}`,
  adminCategories: '/api/admin/training-categories',
  adminCategory: (id: number) => `/api/admin/training-categories/${id}`,
  adminSessions: '/api/admin/training-sessions',
  adminSession: (id: number) => `/api/admin/training-sessions/${id}`,
  adminEnrollments: '/api/admin/training-enrollments',
  adminEnrollmentStatus: (id: number) => `/api/admin/training-enrollments/${id}/status`,
} as const;

export const unwrapTrainingData = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') throw new Error(response.message);
  return response.data;
};
