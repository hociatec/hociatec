import { lazyPage } from './routeLazy';

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
export const CategoriesListPage = lazyPage(
  () => import('@/features/admin/catalog/pages/CategoriesListPage'),
  'CategoriesListPage',
);
export const BrandsListPage = lazyPage(
  () => import('@/features/admin/catalog/pages/BrandsListPage'),
  'BrandsListPage',
);
export const BrandFormPage = lazyPage(
  () => import('@/features/admin/catalog/pages/BrandFormPage'),
  'BrandFormPage',
);
export const CategoryFormPage = lazyPage(
  () => import('@/features/admin/catalog/pages/CategoryFormPage'),
  'CategoryFormPage',
);
export const ProductsListPage = lazyPage(
  () => import('@/features/admin/catalog/pages/ProductsListPage'),
  'ProductsListPage',
);
export const ProductFormPage = lazyPage(
  () => import('@/features/admin/catalog/pages/ProductFormPage'),
  'ProductFormPage',
);
export const QuotesListPage = lazyPage(
  () => import('@/features/admin/quotes/pages/QuotesListPage'),
  'QuotesListPage',
);
export const QuoteFormPage = lazyPage(
  () => import('@/features/admin/quotes/pages/QuoteFormPage'),
  'QuoteFormPage',
);
export const AdminQuoteDetailPage = lazyPage(
  () => import('@/features/admin/quotes/pages/AdminQuoteDetailPage'),
  'AdminQuoteDetailPage',
);
export const ServicesListPage = lazyPage(
  () => import('@/features/admin/quotes/pages/ServicesListPage'),
  'ServicesListPage',
);
export const ServiceFormPage = lazyPage(
  () => import('@/features/admin/quotes/pages/ServiceFormPage'),
  'ServiceFormPage',
);
export const OrdersListPage = lazyPage(
  () => import('@/features/admin/orders/pages/OrdersListPage'),
  'OrdersListPage',
);
export const AdminOrderDetailPage = lazyPage(
  () => import('@/features/admin/orders/pages/AdminOrderDetailPage'),
  'AdminOrderDetailPage',
);
export const PaymentsListPage = lazyPage(
  () => import('@/features/admin/payments/pages/PaymentsListPage'),
  'PaymentsListPage',
);
export const PaymentDetailPage = lazyPage(
  () => import('@/features/admin/payments/pages/PaymentDetailPage'),
  'PaymentDetailPage',
);
export const AdminCustomersListPage = lazyPage(
  () => import('@/features/admin/customers/pages/AdminCustomersListPage'),
  'AdminCustomersListPage',
);
export const AdminCustomerDetailPage = lazyPage(
  () => import('@/features/admin/customers/pages/AdminCustomerDetailPage'),
  'AdminCustomerDetailPage',
);
export const AdminCustomerVoucherPage = lazyPage(
  () => import('@/features/admin/customers/pages/AdminCustomerVoucherPage'),
  'AdminCustomerVoucherPage',
);
export const AdminLoyaltyPage = lazyPage(
  () => import('@/features/admin/loyalty/pages/AdminLoyaltyPage'),
  'AdminLoyaltyPage',
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
export const PromotionsListPage = lazyPage(
  () => import('@/features/admin/promotions/pages/PromotionsListPage'),
  'PromotionsListPage',
);
export const PromotionFormPage = lazyPage(
  () => import('@/features/admin/promotions/pages/PromotionFormPage'),
  'PromotionFormPage',
);
export const VouchersPage = lazyPage(
  () => import('@/features/admin/vouchers/pages/VouchersPage'),
  'VouchersPage',
);
export const VoucherFormPage = lazyPage(
  () => import('@/features/admin/vouchers/pages/VoucherFormPage'),
  'VoucherFormPage',
);
export const AdminAuditsListPage = lazyPage(
  () => import('@/features/admin/audits/pages/AdminAuditsListPage'),
  'AdminAuditsListPage',
);
export const AdminTradeInsPage = lazyPage(() => import('@/features/admin/tradeIns/AdminTradeInsPage'), 'AdminTradeInsPage');
export const AdminBetaTestersPage = lazyPage(
  () => import('@/features/admin/betaTest/pages/AdminBetaTestersPage'),
  'AdminBetaTestersPage',
);
export const AdminBetaCampaignsPage = lazyPage(() => import('@/features/admin/betaTest/pages/AdminBetaCampaignsPage'), 'AdminBetaCampaignsPage');
export const AdminBugReportsPage = lazyPage(
  () => import('@/features/admin/betaTest/pages/AdminBugReportsPage'),
  'AdminBugReportsPage',
);
export const AdminAuditDetailPage = lazyPage(
  () => import('@/features/admin/audits/pages/AdminAuditDetailPage'),
  'AdminAuditDetailPage',
);
