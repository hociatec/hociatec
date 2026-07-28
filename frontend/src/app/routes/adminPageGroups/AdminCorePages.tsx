import { lazyPage } from '../routeLazy';

export const AdminLayout = lazyPage(
  () => import('@/features/admin/layout/AdminLayout'),
  'AdminLayout',
);
export const AdminDashboardPage = lazyPage(
  () => import('@/features/admin/dashboard/pages/AdminDashboardPage'),
  'AdminDashboardPage',
);
export const AdminOperationsPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminOperationsPage'),
  'AdminOperationsPage',
);
export const AdminBackupsPage = lazyPage(
  () => import('@/features/admin/backups/pages/AdminBackupsPage'),
  'AdminBackupsPage',
);
