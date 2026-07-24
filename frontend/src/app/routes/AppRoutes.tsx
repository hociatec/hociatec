import { Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { AdminRoute } from '@/features/admin/components/AdminRoute';
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute';
import { LoadingState } from '@/shared/components/ui/page-state';

import {
  AdminLayout,
  PrestationFormPage,
  PrestationsListPage,
  SchedulePage,
  AdminDashboardPage,
  AdminOperationsPage,
  AdminBackupsPage,
  AdminTrainingsPage,
  TrainingFormPage,
  TrainingSessionsPage,
  TrainingSessionFormPage,
  TrainingEnrollmentsPage,
  TrainingCategoriesPage,
  LoginPage,
  RegisterPage,
  ForgotPasswordPage,
  ResetPasswordPage,
  AppointmentBookingPage,
  MyAppointmentsPage,
  CategoriesListPage,
  BrandsListPage,
  BrandFormPage,
  CategoryFormPage,
  ProductsListPage,
  ProductFormPage,
  CategoryPage,
  ProductPage,
  SellingTypePage,
  CatalogSearchPage,
  GlobalSearchPage,
  CartPage,
  HomePage,
  ProfilePage,
  ClientDashboardPage,
  QuotesListPage,
  AddressesPage,
  QuoteFormPage,
  AdminQuoteDetailPage,
  ServicesListPage,
  ServiceFormPage,
  CreateQuotePage,
  ServicesCatalogPage,
  ServiceDetailPage,
  TrainingsCatalogPage,
  TrainingDetailPage,
  MyTrainingsPage,
  MyTrainingDetailPage,
  MyQuotesPage,
  MyQuoteDetailPage,
  OrdersListPage,
  AdminOrderDetailPage,
  PaymentsListPage,
  PaymentDetailPage,
  AdminCustomersListPage,
  AdminLoyaltyPage,
  AdminCustomerDetailPage,
  AdminCustomerVoucherPage,
  MyVouchersPage,
  MyOrdersPage,
  OrderDetailPage,
  CheckoutSuccessPage,
  ContactPage,
  ActivationPage,
  RequestAuditPage,
  MyAuditsPage,
  MyAuditDetailPage,
  AdminAuditsListPage,
  MarketingCampaignsPage,
  MarketingCampaignFormPage,
  MarketingTemplatesListPage,
  MarketingTemplateDetailPage,
  MarketingTemplateFormPage,
  PromotionsListPage,
  PromotionFormPage,
  VouchersPage,
  VoucherFormPage,
  AdminAuditDetailPage,
  CguPage,
  CgvPage,
  PrivacyPage,
  MentionsPage,
  MyFavoritesPage,
} from './AppRoutePages';
const RouteFallback = () => (
  <div className="site-layout">
    <div className="site-layout__content">
      <LoadingState className="min-h-[40vh]">Chargement de la page...</LoadingState>
    </div>
  </div>
);

export const AppRoutes = () => (
  <Suspense fallback={<RouteFallback />}>
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password/:token" element={<ResetPasswordPage />} />
      <Route path="/contact" element={<ContactPage />} />
      <Route path="/legal/cgu" element={<CguPage />} />
      <Route path="/legal/cgv" element={<CgvPage />} />
      <Route path="/legal/confidentialite" element={<PrivacyPage />} />
      <Route path="/legal/mentions-legales" element={<MentionsPage />} />
      <Route path="/activation/:token" element={<ActivationPage />} />
      <Route path="/catalogue/produits/:slug" element={<ProductPage />} />
      <Route path="/catalogue/recherche" element={<CatalogSearchPage />} />
      <Route path="/recherche" element={<GlobalSearchPage />} />
      <Route path="/catalogue/vente" element={<SellingTypePage sellingType="sale" title="Vente" />} />
      <Route path="/catalogue/location" element={<SellingTypePage sellingType="rental" title="Location" />} />
      <Route path="/services" element={<ServicesCatalogPage />} />
      <Route path="/services/:serviceId" element={<ServiceDetailPage />} />
      <Route path="/formations" element={<TrainingsCatalogPage />} />
      <Route path="/formations/:slug" element={<TrainingDetailPage />} />
      <Route path="/panier" element={<CartPage />} />
      <Route path="/catalogue/:slug" element={<CategoryPage />} />
      <Route path="/devis/nouveau" element={<CreateQuotePage />} />
      <Route
        path="/quotes/me"
        element={
          <ProtectedRoute>
            <MyQuotesPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/quotes/me/:quoteId"
        element={
          <ProtectedRoute>
            <MyQuoteDetailPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/orders/me"
        element={
          <ProtectedRoute>
            <MyOrdersPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/vouchers/me"
        element={
          <ProtectedRoute>
            <MyVouchersPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/trainings/me"
        element={
          <ProtectedRoute>
            <MyTrainingsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/trainings/me/:enrollmentId"
        element={
          <ProtectedRoute>
            <MyTrainingDetailPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/orders/:orderId"
        element={
          <ProtectedRoute>
            <OrderDetailPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/checkout/success"
        element={
          <ProtectedRoute>
            <CheckoutSuccessPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/mon-espace"
        element={
          <ProtectedRoute>
            <ClientDashboardPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile"
        element={
          <ProtectedRoute>
            <ProfilePage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/favorites"
        element={
          <ProtectedRoute>
            <MyFavoritesPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/profile/addresses"
        element={
          <ProtectedRoute>
            <AddressesPage />
          </ProtectedRoute>
        }
      />

      <Route path="/appointments/book" element={<AppointmentBookingPage />} />
      <Route
        path="/appointments/me"
        element={
          <ProtectedRoute>
            <MyAppointmentsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/audits/request"
        element={
          <ProtectedRoute>
            <RequestAuditPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/audits/me"
        element={
          <ProtectedRoute>
            <MyAuditsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/audits/me/:auditId"
        element={
          <ProtectedRoute>
            <MyAuditDetailPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/admin/*"
        element={
          <ProtectedRoute>
            <AdminRoute>
              <AdminLayout />
            </AdminRoute>
          </ProtectedRoute>
        }
      >
        <Route index element={<AdminDashboardPage />} />
        <Route path="operations" element={<AdminOperationsPage />} />
        <Route path="backups" element={<AdminBackupsPage />} />
        <Route path="trainings">
          <Route index element={<AdminTrainingsPage />} />
          <Route path="new" element={<TrainingFormPage />} />
          <Route path=":trainingId/edit" element={<TrainingFormPage />} />
          <Route path="sessions" element={<TrainingSessionsPage />} />
          <Route path="sessions/new" element={<TrainingSessionFormPage />} />
          <Route path="sessions/:sessionId/edit" element={<TrainingSessionFormPage />} />
          <Route path="enrollments" element={<TrainingEnrollmentsPage />} />
          <Route path="categories" element={<TrainingCategoriesPage />} />
        </Route>
        <Route path="appointments">
          <Route index element={<Navigate to="prestations" replace />} />
          <Route path="prestations" element={<PrestationsListPage />} />
          <Route path="prestations/new" element={<PrestationFormPage />} />
          <Route path="prestations/:prestationId/edit" element={<PrestationFormPage />} />
          <Route path="schedule" element={<SchedulePage />} />
        </Route>
        <Route path="catalog">
          <Route index element={<Navigate to="categories" replace />} />
          <Route path="categories">
            <Route index element={<CategoriesListPage />} />
            <Route path="new" element={<CategoryFormPage />} />
            <Route path=":categoryId/edit" element={<CategoryFormPage />} />
          </Route>
          <Route path="brands">
            <Route index element={<BrandsListPage />} />
            <Route path="new" element={<BrandFormPage />} />
            <Route path=":brandId/edit" element={<BrandFormPage />} />
          </Route>
          <Route path="products">
            <Route index element={<ProductsListPage />} />
            <Route path="new" element={<ProductFormPage />} />
            <Route path=":productId/edit" element={<ProductFormPage />} />
          </Route>
        </Route>
        <Route path="quotes">
          <Route index element={<QuotesListPage />} />
          <Route path="new" element={<QuoteFormPage />} />
          <Route path=":quoteId" element={<AdminQuoteDetailPage />} />
          <Route path=":quoteId/edit" element={<QuoteFormPage />} />
        </Route>
        <Route path="services">
          <Route index element={<ServicesListPage />} />
          <Route path="new" element={<ServiceFormPage />} />
          <Route path=":serviceId/edit" element={<ServiceFormPage />} />
        </Route>
        <Route path="orders">
          <Route index element={<OrdersListPage />} />
          <Route path=":orderId" element={<AdminOrderDetailPage />} />
        </Route>
        <Route path="payments">
          <Route index element={<PaymentsListPage />} />
          <Route path=":paymentId" element={<PaymentDetailPage />} />
        </Route>
        <Route path="customers">
          <Route index element={<AdminCustomersListPage />} />
          <Route path=":customerId" element={<AdminCustomerDetailPage />} />
          <Route path=":customerId/vouchers/new" element={<AdminCustomerVoucherPage />} />
        </Route>
        <Route path="loyalty" element={<AdminLoyaltyPage />} />
        <Route path="marketing">
          <Route index element={<MarketingCampaignsPage />} />
          <Route path="new" element={<MarketingCampaignFormPage />} />
          <Route path="templates">
            <Route index element={<MarketingTemplatesListPage />} />
            <Route path="new" element={<MarketingTemplateFormPage />} />
            <Route path=":templateId" element={<MarketingTemplateDetailPage />} />
            <Route path=":templateId/edit" element={<MarketingTemplateFormPage />} />
          </Route>
        </Route>
        <Route path="transactional-emails">
          <Route index element={<MarketingTemplatesListPage />} />
          <Route path="new" element={<MarketingTemplateFormPage />} />
          <Route path=":templateId" element={<MarketingTemplateDetailPage />} />
          <Route path=":templateId/edit" element={<MarketingTemplateFormPage />} />
        </Route>
        <Route path="promotions">
          <Route index element={<PromotionsListPage />} />
          <Route path="new" element={<PromotionFormPage />} />
          <Route path=":promotionId/edit" element={<PromotionFormPage />} />
        </Route>
        <Route path="vouchers">
          <Route index element={<VouchersPage />} />
          <Route path="new" element={<VoucherFormPage />} />
          <Route path=":voucherId/edit" element={<VoucherFormPage />} />
        </Route>
        <Route path="audits">
          <Route index element={<AdminAuditsListPage />} />
          <Route path=":auditId" element={<AdminAuditDetailPage />} />
        </Route>
      </Route>

      <Route
        path="/appointments/admin"
        element={
          <ProtectedRoute>
            <Navigate to="/admin/appointments/prestations" replace />
          </ProtectedRoute>
        }
      />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  </Suspense>
);
