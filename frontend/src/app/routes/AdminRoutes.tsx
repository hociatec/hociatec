import { Navigate } from 'react-router';

import { AdminRoute } from '@/features/admin/components/AdminRoute';
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute';
import {
  AdminAuditDetailPage,
  AdminAuditsListPage,
  AdminBackupsPage,
  AdminBetaCampaignsPage,
  AdminBetaTestersPage,
  AdminBugReportsPage,
  AdminCustomerDetailPage,
  AdminCustomerVoucherPage,
  AdminCustomersListPage,
  AdminDashboardPage,
  AdminLayout,
  AdminLoyaltyPage,
  AdminNewsFormPage,
  AdminNewsListPage,
  AdminOperationsPage,
  AdminOrderDetailPage,
  AdminQuoteDetailPage,
  AdminTradeInsPage,
  AdminTrainingsPage,
  BrandFormPage,
  BrandsListPage,
  CategoriesListPage,
  CategoryFormPage,
  MarketingCampaignFormPage,
  MarketingCampaignsPage,
  MarketingTemplateDetailPage,
  MarketingTemplateFormPage,
  MarketingTemplatesListPage,
  OrdersListPage,
  PaymentDetailPage,
  PaymentsListPage,
  PrestationFormPage,
  PrestationsListPage,
  ProductFormPage,
  ProductsListPage,
  PromotionFormPage,
  PromotionsListPage,
  QuoteFormPage,
  QuotesListPage,
  SchedulePage,
  ServiceFormPage,
  ServicesListPage,
  TrainingCategoriesPage,
  TrainingEnrollmentsPage,
  TrainingFormPage,
  TrainingSessionFormPage,
  TrainingSessionsPage,
  VoucherFormPage,
  VouchersPage,
} from './AdminRoutePages';
import type { AppRouteDefinition } from './RouteDefinition';

const adminElement = (
  <ProtectedRoute>
    <AdminRoute>
      <AdminLayout />
    </AdminRoute>
  </ProtectedRoute>
);

export const adminRoutes: AppRouteDefinition = {
  path: '/admin/*',
  element: adminElement,
  children: [
    { index: true, element: <AdminDashboardPage /> },
    { path: 'operations', element: <AdminOperationsPage /> },
    { path: 'backups', element: <AdminBackupsPage /> },
    {
      path: 'trainings',
      children: [
        { index: true, element: <AdminTrainingsPage /> },
        { path: 'new', element: <TrainingFormPage /> },
        { path: ':trainingId/edit', element: <TrainingFormPage /> },
        { path: 'sessions', element: <TrainingSessionsPage /> },
        { path: 'sessions/new', element: <TrainingSessionFormPage /> },
        { path: 'sessions/:sessionId/edit', element: <TrainingSessionFormPage /> },
        { path: 'enrollments', element: <TrainingEnrollmentsPage /> },
        { path: 'categories', element: <TrainingCategoriesPage /> },
      ],
    },
    {
      path: 'appointments',
      children: [
        { index: true, element: <Navigate to="motifs" replace /> },
        { path: 'motifs', element: <PrestationsListPage /> },
        { path: 'motifs/new', element: <PrestationFormPage /> },
        { path: 'motifs/:prestationId/edit', element: <PrestationFormPage /> },
        { path: 'schedule', element: <SchedulePage /> },
      ],
    },
    {
      path: 'catalog',
      children: [
        { index: true, element: <Navigate to="categories" replace /> },
        { path: 'categories', children: [{ index: true, element: <CategoriesListPage /> }, { path: 'new', element: <CategoryFormPage /> }, { path: ':categoryId/edit', element: <CategoryFormPage /> }] },
        { path: 'brands', children: [{ index: true, element: <BrandsListPage /> }, { path: 'new', element: <BrandFormPage /> }, { path: ':brandId/edit', element: <BrandFormPage /> }] },
        { path: 'products', children: [{ index: true, element: <ProductsListPage /> }, { path: 'new', element: <ProductFormPage /> }, { path: ':productId/edit', element: <ProductFormPage /> }] },
      ],
    },
    {
      path: 'quotes',
      children: [
        { index: true, element: <QuotesListPage /> },
        { path: 'new', element: <QuoteFormPage /> },
        { path: ':quoteId', element: <AdminQuoteDetailPage /> },
        { path: ':quoteId/edit', element: <QuoteFormPage /> },
      ],
    },
    { path: 'services', children: [{ index: true, element: <ServicesListPage /> }, { path: 'new', element: <ServiceFormPage /> }, { path: ':serviceId/edit', element: <ServiceFormPage /> }] },
    { path: 'orders', children: [{ index: true, element: <OrdersListPage /> }, { path: ':orderId', element: <AdminOrderDetailPage /> }] },
    { path: 'payments', children: [{ index: true, element: <PaymentsListPage /> }, { path: ':paymentId', element: <PaymentDetailPage /> }] },
    { path: 'customers', children: [{ index: true, element: <AdminCustomersListPage /> }, { path: ':customerId', element: <AdminCustomerDetailPage /> }, { path: ':customerId/vouchers/new', element: <AdminCustomerVoucherPage /> }] },
    { path: 'loyalty', element: <AdminLoyaltyPage /> },
    { path: 'news', children: [{ index: true, element: <AdminNewsListPage /> }, { path: 'new', element: <AdminNewsFormPage /> }, { path: ':newsId/edit', element: <AdminNewsFormPage /> }] },
    {
      path: 'marketing',
      children: [
        { index: true, element: <MarketingCampaignsPage /> },
        { path: 'new', element: <MarketingCampaignFormPage /> },
        { path: 'templates', children: [{ index: true, element: <MarketingTemplatesListPage /> }, { path: 'new', element: <MarketingTemplateFormPage /> }, { path: ':templateId', element: <MarketingTemplateDetailPage /> }, { path: ':templateId/edit', element: <MarketingTemplateFormPage /> }] },
      ],
    },
    { path: 'transactional-emails', children: [{ index: true, element: <MarketingTemplatesListPage /> }, { path: 'new', element: <MarketingTemplateFormPage /> }, { path: ':templateId', element: <MarketingTemplateDetailPage /> }, { path: ':templateId/edit', element: <MarketingTemplateFormPage /> }] },
    { path: 'promotions', children: [{ index: true, element: <PromotionsListPage /> }, { path: 'new', element: <PromotionFormPage /> }, { path: ':promotionId/edit', element: <PromotionFormPage /> }] },
    { path: 'vouchers', children: [{ index: true, element: <VouchersPage /> }, { path: 'new', element: <VoucherFormPage /> }, { path: ':voucherId/edit', element: <VoucherFormPage /> }] },
    { path: 'audits', children: [{ index: true, element: <AdminAuditsListPage /> }, { path: ':auditId', element: <AdminAuditDetailPage /> }] },
    { path: 'beta-testers', element: <AdminBetaTestersPage /> },
    { path: 'beta-campaigns', element: <AdminBetaCampaignsPage /> },
    { path: 'beta-reports', element: <AdminBugReportsPage /> },
    { path: 'trade-ins', element: <AdminTradeInsPage /> },
  ],
};
