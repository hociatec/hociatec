import type { ReactNode } from 'react';
import { Navigate, Route } from 'react-router-dom';

import { AdminRoute } from '@/features/admin/components/AdminRoute';
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute';
import {
  ActivationPage, AddressesPage, AdminAuditDetailPage, AdminAuditsListPage, AdminBackupsPage, AdminCustomerDetailPage, AdminCustomerVoucherPage, AdminCustomersListPage, AdminDashboardPage, AdminLayout, AdminLoyaltyPage, AdminOperationsPage, AdminOrderDetailPage, AdminQuoteDetailPage, AdminTrainingsPage, AppointmentBookingPage, BrandFormPage, BrandsListPage, CartPage, CategoryFormPage, CategoryPage, CategoriesListPage, CatalogSearchPage, CheckoutSuccessPage, CguPage, CgvPage, ClientDashboardPage, ContactPage, CreateQuotePage, ForgotPasswordPage, GlobalSearchPage, HomePage, LoginPage, MarketingCampaignFormPage, MarketingCampaignsPage, MarketingTemplateDetailPage, MarketingTemplateFormPage, MarketingTemplatesListPage, MentionsPage, MyAppointmentsPage, MyAuditDetailPage, MyAuditsPage, MyFavoritesPage, MyOrdersPage, MyQuoteDetailPage, MyQuotesPage, MyTrainingDetailPage, MyTrainingsPage, MyVouchersPage, OrderDetailPage, OrdersListPage, PaymentsListPage, PaymentDetailPage, PrestationFormPage, PrestationsListPage, PrivacyPage, ProductFormPage, ProductsListPage, ProductPage, ProfilePage, PromotionFormPage, PromotionsListPage, QuoteFormPage, QuotesListPage, RegisterPage, RequestAuditPage, ResetPasswordPage, SchedulePage, ServiceDetailPage, ServiceFormPage, ServicesCatalogPage, ServicesListPage, SellingTypePage, TrainingCategoriesPage, TrainingDetailPage, TrainingEnrollmentsPage, TrainingFormPage, TrainingSessionFormPage, TrainingSessionsPage, TrainingsCatalogPage, VoucherFormPage, VouchersPage,
} from './AppRoutePages';

export interface AppRouteDefinition {
  path?: string;
  index?: boolean;
  element?: ReactNode;
  children?: AppRouteDefinition[];
}

const protectedElement = (element: ReactNode) => <ProtectedRoute>{element}</ProtectedRoute>;
const adminElement = <ProtectedRoute><AdminRoute><AdminLayout /></AdminRoute></ProtectedRoute>;

export const publicRoutes: AppRouteDefinition[] = [
  { path: '/', element: <HomePage /> },
  { path: '/login', element: <LoginPage /> },
  { path: '/register', element: <RegisterPage /> },
  { path: '/forgot-password', element: <ForgotPasswordPage /> },
  { path: '/reset-password/:token', element: <ResetPasswordPage /> },
  { path: '/contact', element: <ContactPage /> },
  { path: '/legal/cgu', element: <CguPage /> },
  { path: '/legal/cgv', element: <CgvPage /> },
  { path: '/legal/confidentialite', element: <PrivacyPage /> },
  { path: '/legal/mentions-legales', element: <MentionsPage /> },
  { path: '/activation/:token', element: <ActivationPage /> },
  { path: '/catalogue/produits/:slug', element: <ProductPage /> },
  { path: '/catalogue/recherche', element: <CatalogSearchPage /> },
  { path: '/catalogue/vente', element: <SellingTypePage sellingType="sale" title="Vente" /> },
  { path: '/catalogue/location', element: <SellingTypePage sellingType="rental" title="Location" /> },
  { path: '/catalogue/:slug', element: <CategoryPage /> },
  { path: '/recherche', element: <GlobalSearchPage /> },
  { path: '/services', element: <ServicesCatalogPage /> },
  { path: '/services/:serviceId', element: <ServiceDetailPage /> },
  { path: '/formations', element: <TrainingsCatalogPage /> },
  { path: '/formations/:slug', element: <TrainingDetailPage /> },
  { path: '/panier', element: <CartPage /> },
  { path: '/devis/nouveau', element: <CreateQuotePage /> },
  { path: '/appointments/book', element: <AppointmentBookingPage /> },
];

export const protectedRoutes: AppRouteDefinition[] = [
  { path: '/quotes/me', element: protectedElement(<MyQuotesPage />) },
  { path: '/quotes/me/:quoteId', element: protectedElement(<MyQuoteDetailPage />) },
  { path: '/orders/me', element: protectedElement(<MyOrdersPage />) },
  { path: '/vouchers/me', element: protectedElement(<MyVouchersPage />) },
  { path: '/trainings/me', element: protectedElement(<MyTrainingsPage />) },
  { path: '/trainings/me/:enrollmentId', element: protectedElement(<MyTrainingDetailPage />) },
  { path: '/orders/:orderId', element: protectedElement(<OrderDetailPage />) },
  { path: '/checkout/success', element: protectedElement(<CheckoutSuccessPage />) },
  { path: '/mon-espace', element: protectedElement(<ClientDashboardPage />) },
  { path: '/profile', element: protectedElement(<ProfilePage />) },
  { path: '/favorites', element: protectedElement(<MyFavoritesPage />) },
  { path: '/profile/addresses', element: protectedElement(<AddressesPage />) },
  { path: '/appointments/me', element: protectedElement(<MyAppointmentsPage />) },
  { path: '/audits/request', element: protectedElement(<RequestAuditPage />) },
  { path: '/audits/me', element: protectedElement(<MyAuditsPage />) },
  { path: '/audits/me/:auditId', element: protectedElement(<MyAuditDetailPage />) },
  { path: '/appointments/admin', element: protectedElement(<Navigate to="/admin/appointments/prestations" replace />) },
];

export const adminRoutes: AppRouteDefinition = {
  path: '/admin/*',
  element: adminElement,
  children: [
    { index: true, element: <AdminDashboardPage /> },
    { path: 'operations', element: <AdminOperationsPage /> },
    { path: 'backups', element: <AdminBackupsPage /> },
    { path: 'trainings', children: [
      { index: true, element: <AdminTrainingsPage /> }, { path: 'new', element: <TrainingFormPage /> }, { path: ':trainingId/edit', element: <TrainingFormPage /> },
      { path: 'sessions', element: <TrainingSessionsPage /> }, { path: 'sessions/new', element: <TrainingSessionFormPage /> }, { path: 'sessions/:sessionId/edit', element: <TrainingSessionFormPage /> },
      { path: 'enrollments', element: <TrainingEnrollmentsPage /> }, { path: 'categories', element: <TrainingCategoriesPage /> },
    ] },
    { path: 'appointments', children: [
      { index: true, element: <Navigate to="prestations" replace /> },
      { path: 'prestations', element: <PrestationsListPage /> }, { path: 'prestations/new', element: <PrestationFormPage /> }, { path: 'prestations/:prestationId/edit', element: <PrestationFormPage /> }, { path: 'schedule', element: <SchedulePage /> },
    ] },
    { path: 'catalog', children: [
      { index: true, element: <Navigate to="categories" replace /> },
      { path: 'categories', children: [{ index: true, element: <CategoriesListPage /> }, { path: 'new', element: <CategoryFormPage /> }, { path: ':categoryId/edit', element: <CategoryFormPage /> }] },
      { path: 'brands', children: [{ index: true, element: <BrandsListPage /> }, { path: 'new', element: <BrandFormPage /> }, { path: ':brandId/edit', element: <BrandFormPage /> }] },
      { path: 'products', children: [{ index: true, element: <ProductsListPage /> }, { path: 'new', element: <ProductFormPage /> }, { path: ':productId/edit', element: <ProductFormPage /> }] },
    ] },
    { path: 'quotes', children: [{ index: true, element: <QuotesListPage /> }, { path: 'new', element: <QuoteFormPage /> }, { path: ':quoteId', element: <AdminQuoteDetailPage /> }, { path: ':quoteId/edit', element: <QuoteFormPage /> }] },
    { path: 'services', children: [{ index: true, element: <ServicesListPage /> }, { path: 'new', element: <ServiceFormPage /> }, { path: ':serviceId/edit', element: <ServiceFormPage /> }] },
    { path: 'orders', children: [{ index: true, element: <OrdersListPage /> }, { path: ':orderId', element: <AdminOrderDetailPage /> }] },
    { path: 'payments', children: [{ index: true, element: <PaymentsListPage /> }, { path: ':paymentId', element: <PaymentDetailPage /> }] },
    { path: 'customers', children: [{ index: true, element: <AdminCustomersListPage /> }, { path: ':customerId', element: <AdminCustomerDetailPage /> }, { path: ':customerId/vouchers/new', element: <AdminCustomerVoucherPage /> }] },
    { path: 'loyalty', element: <AdminLoyaltyPage /> },
    { path: 'marketing', children: [
      { index: true, element: <MarketingCampaignsPage /> }, { path: 'new', element: <MarketingCampaignFormPage /> }, { path: 'templates', children: [{ index: true, element: <MarketingTemplatesListPage /> }, { path: 'new', element: <MarketingTemplateFormPage /> }, { path: ':templateId', element: <MarketingTemplateDetailPage /> }, { path: ':templateId/edit', element: <MarketingTemplateFormPage /> }] },
    ] },
    { path: 'transactional-emails', children: [{ index: true, element: <MarketingTemplatesListPage /> }, { path: 'new', element: <MarketingTemplateFormPage /> }, { path: ':templateId', element: <MarketingTemplateDetailPage /> }, { path: ':templateId/edit', element: <MarketingTemplateFormPage /> }] },
    { path: 'promotions', children: [{ index: true, element: <PromotionsListPage /> }, { path: 'new', element: <PromotionFormPage /> }, { path: ':promotionId/edit', element: <PromotionFormPage /> }] },
    { path: 'vouchers', children: [{ index: true, element: <VouchersPage /> }, { path: 'new', element: <VoucherFormPage /> }, { path: ':voucherId/edit', element: <VoucherFormPage /> }] },
    { path: 'audits', children: [{ index: true, element: <AdminAuditsListPage /> }, { path: ':auditId', element: <AdminAuditDetailPage /> }] },
  ],
};

export const renderRoutes = (routes: AppRouteDefinition[], parentKey = 'route') => routes.map((route, index) => {
  const key = `${parentKey}-${route.path ?? 'index'}-${index}`;
  const children = route.children ? renderRoutes(route.children, `${parentKey}-${route.path ?? index}`) : null;
  if (route.index) return <Route key={key} index element={route.element} />;
  return <Route key={key} path={route.path} element={route.element}>{children}</Route>;
});
