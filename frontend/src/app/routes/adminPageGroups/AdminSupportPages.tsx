import { lazyPage } from '../routeLazy';

export const PrestationFormPage = lazyPage(
  () => import('@/features/admin/appointments/pages/PrestationFormPage'),
  'PrestationFormPage',
);
export const PrestationsListPage = lazyPage(
  () => import('@/features/admin/appointments/pages/PrestationsListPage'),
  'PrestationsListPage',
);
export const SchedulePage = lazyPage(
  () => import('@/features/admin/appointments/pages/SchedulePage'),
  'SchedulePage',
);
export const AdminAuditsListPage = lazyPage(
  () => import('@/features/admin/audits/pages/AdminAuditsListPage'),
  'AdminAuditsListPage',
);
export const AdminAuditDetailPage = lazyPage(
  () => import('@/features/admin/audits/pages/AdminAuditDetailPage'),
  'AdminAuditDetailPage',
);
export const AdminTradeInsPage = lazyPage(
  () => import('@/features/admin/tradeIns/AdminTradeInsPage'),
  'AdminTradeInsPage',
);
export const AdminBetaTestersPage = lazyPage(
  () => import('@/features/admin/betaTest/pages/AdminBetaTestersPage'),
  'AdminBetaTestersPage',
);
export const AdminBetaCampaignsPage = lazyPage(
  () => import('@/features/admin/betaTest/pages/AdminBetaCampaignsPage'),
  'AdminBetaCampaignsPage',
);
export const AdminBugReportsPage = lazyPage(
  () => import('@/features/admin/betaTest/pages/AdminBugReportsPage'),
  'AdminBugReportsPage',
);
