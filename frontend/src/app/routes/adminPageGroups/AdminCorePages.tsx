import { lazyPage } from '../routeLazy';

export const AdminLayout = lazyPage(
  () => import('@/features/admin/layout/AdminLayout'),
  'AdminLayout',
);
export const AdminDashboardPage = lazyPage(
  () => import('@/features/admin/dashboard/pages/AdminDashboardPage'),
  'AdminDashboardPage',
);
export const AdminBackupsPage = lazyPage(
  () => import('@/features/admin/backups/pages/AdminBackupsPage'),
  'AdminBackupsPage',
);
export const AdminUiCatalogPage = lazyPage(
  () => import('@/features/admin/system/pages/AdminUiCatalogPage'),
  'AdminUiCatalogPage',
);
export const AdminExportsPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminExportsPage'),
  'AdminExportsPage',
);
