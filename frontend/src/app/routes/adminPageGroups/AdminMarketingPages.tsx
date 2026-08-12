import { lazyPage } from '../routeLazy';

export const AdminNewsListPage = lazyPage(
  () => import('@/features/admin/news/pages/AdminNewsListPage'),
  'AdminNewsListPage',
);
export const AdminNewsFormPage = lazyPage(
  () => import('@/features/admin/news/pages/AdminNewsFormPage'),
  'AdminNewsFormPage',
);
export const MarketingCampaignsPage = lazyPage(
  () => import('@/features/admin/marketing/pages/MarketingCampaignsPage'),
  'MarketingCampaignsPage',
);
export const MarketingCampaignFormPage = lazyPage(
  () => import('@/features/admin/marketing/pages/MarketingCampaignFormPage'),
  'MarketingCampaignFormPage',
);
export const MarketingTemplatesListPage = lazyPage(
  () => import('@/features/admin/marketing/pages/MarketingTemplatesListPage'),
  'MarketingTemplatesListPage',
);
export const MarketingTemplateDetailPage = lazyPage(
  () => import('@/features/admin/marketing/pages/MarketingTemplateDetailPage'),
  'MarketingTemplateDetailPage',
);
export const MarketingTemplateFormPage = lazyPage(
  () => import('@/features/admin/marketing/pages/MarketingTemplateFormPage'),
  'MarketingTemplateFormPage',
);
export const AdminTransactionalEmailLogsPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminTransactionalEmailLogsPage'),
  'AdminTransactionalEmailLogsPage',
);
