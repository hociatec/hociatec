import { lazyPage } from '../routeLazy';

export const AdminTrainingsPage = lazyPage(
  () => import('@/features/admin/trainings/pages/AdminTrainingsPage'),
  'AdminTrainingsPage',
);
export const TrainingFormPage = lazyPage(
  () => import('@/features/admin/trainings/pages/TrainingFormPage'),
  'TrainingFormPage',
);
export const TrainingSessionsPage = lazyPage(
  () => import('@/features/admin/trainings/pages/TrainingSessionsPage'),
  'TrainingSessionsPage',
);
export const TrainingSessionFormPage = lazyPage(
  () => import('@/features/admin/trainings/pages/TrainingSessionFormPage'),
  'TrainingSessionFormPage',
);
export const TrainingEnrollmentsPage = lazyPage(
  () => import('@/features/admin/trainings/pages/TrainingEnrollmentsPage'),
  'TrainingEnrollmentsPage',
);
export const TrainingCategoriesPage = lazyPage(
  () => import('@/features/admin/trainings/pages/TrainingCategoriesPage'),
  'TrainingCategoriesPage',
);
