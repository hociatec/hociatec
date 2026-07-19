import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

import { AdminRoute } from '@/features/admin/components/AdminRoute';
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute';

const AdminLayout = lazy(() =>
  import('@/features/admin/layout/AdminLayout').then((module) => ({ default: module.AdminLayout })),
);
const PrestationFormPage = lazy(() =>
  import('@/features/admin/appointments/pages/PrestationFormPage').then((module) => ({
    default: module.PrestationFormPage,
  })),
);
const PrestationsListPage = lazy(() =>
  import('@/features/admin/appointments/pages/PrestationsListPage').then((module) => ({
    default: module.PrestationsListPage,
  })),
);
const SchedulePage = lazy(() =>
  import('@/features/admin/appointments/pages/SchedulePage').then((module) => ({
    default: module.SchedulePage,
  })),
);
const AdminDashboardPage = lazy(() =>
  import('@/features/admin/pages/AdminDashboardPage').then((module) => ({
    default: module.AdminDashboardPage,
  })),
);
const AdminOperationsPage = lazy(() =>
  import('@/features/admin/operations/pages/AdminOperationsPage').then((module) => ({
    default: module.AdminOperationsPage,
  })),
);
const LoginPage = lazy(() =>
  import('@/features/auth/pages/LoginPage').then((module) => ({ default: module.LoginPage })),
);
const RegisterPage = lazy(() =>
  import('@/features/auth/pages/RegisterPage').then((module) => ({ default: module.RegisterPage })),
);
const ForgotPasswordPage = lazy(() =>
  import('@/features/auth/pages/ForgotPasswordPage').then((module) => ({ default: module.ForgotPasswordPage })),
);
const ResetPasswordPage = lazy(() =>
  import('@/features/auth/pages/ResetPasswordPage').then((module) => ({ default: module.ResetPasswordPage })),
);
const AppointmentBookingPage = lazy(() =>
  import('@/features/appointments/pages/AppointmentBookingPage').then((module) => ({
    default: module.AppointmentBookingPage,
  })),
);
const MyAppointmentsPage = lazy(() =>
  import('@/features/appointments/pages/MyAppointmentsPage').then((module) => ({
    default: module.MyAppointmentsPage,
  })),
);
const CategoriesListPage = lazy(() =>
  import('@/features/admin/catalog/pages/CategoriesListPage').then((module) => ({
    default: module.CategoriesListPage,
  })),
);
const BrandsListPage = lazy(() =>
  import('@/features/admin/catalog/pages/BrandsListPage').then((module) => ({
    default: module.BrandsListPage,
  })),
);
const BrandFormPage = lazy(() =>
  import('@/features/admin/catalog/pages/BrandFormPage').then((module) => ({
    default: module.BrandFormPage,
  })),
);
const CategoryFormPage = lazy(() =>
  import('@/features/admin/catalog/pages/CategoryFormPage').then((module) => ({
    default: module.CategoryFormPage,
  })),
);
const ProductsListPage = lazy(() =>
  import('@/features/admin/catalog/pages/ProductsListPage').then((module) => ({
    default: module.ProductsListPage,
  })),
);
const ProductFormPage = lazy(() =>
  import('@/features/admin/catalog/pages/ProductFormPage').then((module) => ({
    default: module.ProductFormPage,
  })),
);
const CategoryPage = lazy(() =>
  import('@/features/catalog/pages/CategoryPage').then((module) => ({
    default: module.CategoryPage,
  })),
);
const ProductPage = lazy(() =>
  import('@/features/catalog/pages/ProductPage').then((module) => ({
    default: module.ProductPage,
  })),
);
const SellingTypePage = lazy(() =>
  import('@/features/catalog/pages/SellingTypePage').then((module) => ({
    default: module.SellingTypePage,
  })),
);
const CatalogSearchPage = lazy(() =>
  import('@/features/catalog/pages/CatalogSearchPage').then((module) => ({
    default: module.CatalogSearchPage,
  })),
);
const CartPage = lazy(() =>
  import('@/features/cart/pages/CartPage').then((module) => ({ default: module.CartPage })),
);
const HomePage = lazy(() =>
  import('@/features/home/pages/HomePage').then((module) => ({ default: module.HomePage })),
);
const ProfilePage = lazy(() =>
  import('@/features/profile/pages/ProfilePage').then((module) => ({ default: module.ProfilePage })),
);
const ClientDashboardPage = lazy(() =>
  import('@/features/account/pages/ClientDashboardPage').then((module) => ({
    default: module.ClientDashboardPage,
  })),
);
const QuotesListPage = lazy(() =>
  import('@/features/admin/quotes/pages/QuotesListPage').then((module) => ({
    default: module.QuotesListPage,
  })),
);
const AddressesPage = lazy(() =>
  import('@/features/addresses/pages/AddressesPage').then((module) => ({ default: module.AddressesPage })),
);
const QuoteFormPage = lazy(() =>
  import('@/features/admin/quotes/pages/QuoteFormPage').then((module) => ({
    default: module.QuoteFormPage,
  })),
);
const AdminQuoteDetailPage = lazy(() =>
  import('@/features/admin/quotes/pages/AdminQuoteDetailPage').then((module) => ({
    default: module.AdminQuoteDetailPage,
  })),
);
const ServicesListPage = lazy(() =>
  import('@/features/admin/quotes/pages/ServicesListPage').then((module) => ({
    default: module.ServicesListPage,
  })),
);
const ServiceFormPage = lazy(() =>
  import('@/features/admin/quotes/pages/ServiceFormPage').then((module) => ({
    default: module.ServiceFormPage,
  })),
);
const CreateQuotePage = lazy(() =>
  import('@/features/quotes/pages/CreateQuotePage').then((module) => ({ default: module.CreateQuotePage })),
);
const ServicesCatalogPage = lazy(() =>
  import('@/features/quotes/pages/ServicesCatalogPage').then((module) => ({ default: module.ServicesCatalogPage })),
);
const ServiceDetailPage = lazy(() =>
  import('@/features/quotes/pages/ServiceDetailPage').then((module) => ({ default: module.ServiceDetailPage })),
);
const MyQuotesPage = lazy(() =>
  import('@/features/quotes/pages/MyQuotesPage').then((module) => ({ default: module.MyQuotesPage })),
);
const MyQuoteDetailPage = lazy(() =>
  import('@/features/quotes/pages/MyQuoteDetailPage').then((module) => ({ default: module.MyQuoteDetailPage })),
);
const OrdersListPage = lazy(() =>
  import('@/features/admin/orders/pages/OrdersListPage').then((module) => ({
    default: module.OrdersListPage,
  })),
);
const AdminOrderDetailPage = lazy(() =>
  import('@/features/admin/orders/pages/AdminOrderDetailPage').then((module) => ({
    default: module.AdminOrderDetailPage,
  })),
);
const PaymentsListPage = lazy(() =>
  import('@/features/admin/payments/pages/PaymentsListPage').then((module) => ({
    default: module.PaymentsListPage,
  })),
);
const PaymentDetailPage = lazy(() =>
  import('@/features/admin/payments/pages/PaymentDetailPage').then((module) => ({
    default: module.PaymentDetailPage,
  })),
);
const AdminCustomersListPage = lazy(() =>
  import('@/features/admin/customers/pages/AdminCustomersListPage').then((module) => ({
    default: module.AdminCustomersListPage,
  })),
);
const AdminCustomerDetailPage = lazy(() =>
  import('@/features/admin/customers/pages/AdminCustomerDetailPage').then((module) => ({
    default: module.AdminCustomerDetailPage,
  })),
);
const AdminCustomerVoucherPage = lazy(() =>
  import('@/features/admin/customers/pages/AdminCustomerVoucherPage').then((module) => ({
    default: module.AdminCustomerVoucherPage,
  })),
);
const MyVouchersPage = lazy(() =>
  import('@/features/vouchers/pages/MyVouchersPage').then((module) => ({ default: module.MyVouchersPage })),
);
const MyOrdersPage = lazy(() =>
  import('@/features/orders/pages/MyOrdersPage').then((module) => ({ default: module.MyOrdersPage })),
);
const OrderDetailPage = lazy(() =>
  import('@/features/orders/pages/OrderDetailPage').then((module) => ({ default: module.OrderDetailPage })),
);
const CheckoutSuccessPage = lazy(() =>
  import('@/features/orders/pages/CheckoutSuccessPage').then((module) => ({ default: module.CheckoutSuccessPage })),
);
const ContactPage = lazy(() =>
  import('@/features/contact/pages/ContactPage').then((module) => ({ default: module.ContactPage })),
);
const ActivationPage = lazy(() =>
  import('@/features/auth/pages/ActivationPage').then((module) => ({ default: module.ActivationPage })),
);
const RequestAuditPage = lazy(() =>
  import('@/features/audits/pages/RequestAuditPage').then((module) => ({
    default: module.RequestAuditPage,
  })),
);
const MyAuditsPage = lazy(() =>
  import('@/features/audits/pages/MyAuditsPage').then((module) => ({ default: module.MyAuditsPage })),
);
const MyAuditDetailPage = lazy(() =>
  import('@/features/audits/pages/MyAuditDetailPage').then((module) => ({
    default: module.MyAuditDetailPage,
  })),
);
const AdminAuditsListPage = lazy(() =>
  import('@/features/admin/audits/pages/AdminAuditsListPage').then((module) => ({
    default: module.AdminAuditsListPage,
  })),
);
const MarketingCampaignsPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingCampaignsPage').then((module) => ({
    default: module.MarketingCampaignsPage,
  })),
);
const MarketingCampaignFormPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingCampaignFormPage').then((module) => ({
    default: module.MarketingCampaignFormPage,
  })),
);
const MarketingTemplatesListPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingTemplatesListPage').then((module) => ({
    default: module.MarketingTemplatesListPage,
  })),
);
const MarketingTemplateDetailPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingTemplateDetailPage').then((module) => ({
    default: module.MarketingTemplateDetailPage,
  })),
);
const MarketingTemplateFormPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingTemplateFormPage').then((module) => ({
    default: module.MarketingTemplateFormPage,
  })),
);
const PromotionsListPage = lazy(() =>
  import('@/features/admin/promotions/pages/PromotionsListPage').then((module) => ({
    default: module.PromotionsListPage,
  })),
);
const PromotionFormPage = lazy(() =>
  import('@/features/admin/promotions/pages/PromotionFormPage').then((module) => ({
    default: module.PromotionFormPage,
  })),
);
const VouchersPage = lazy(() =>
  import('@/features/admin/vouchers/pages/VouchersPage').then((module) => ({
    default: module.VouchersPage,
  })),
);
const VoucherFormPage = lazy(() =>
  import('@/features/admin/vouchers/pages/VoucherFormPage').then((module) => ({
    default: module.VoucherFormPage,
  })),
);
const AdminAuditDetailPage = lazy(() =>
  import('@/features/admin/audits/pages/AdminAuditDetailPage').then((module) => ({
    default: module.AdminAuditDetailPage,
  })),
);
const CguPage = lazy(() =>
  import('@/features/legal/pages/CguPage').then((module) => ({ default: module.CguPage })),
);
const CgvPage = lazy(() =>
  import('@/features/legal/pages/CgvPage').then((module) => ({ default: module.CgvPage })),
);
const PrivacyPage = lazy(() =>
  import('@/features/legal/pages/PrivacyPage').then((module) => ({ default: module.PrivacyPage })),
);
const MentionsPage = lazy(() =>
  import('@/features/legal/pages/MentionsPage').then((module) => ({ default: module.MentionsPage })),
);
const MyFavoritesPage = lazy(() =>
  import('@/features/favorites/pages/MyFavoritesPage').then((module) => ({
    default: module.MyFavoritesPage,
  })),
);
const RouteFallback = () => (
  <div className="site-layout">
    <div className="site-layout__content px-6 py-16 text-center text-slate-600">Chargement...</div>
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
      <Route path="/catalogue/vente" element={<SellingTypePage sellingType="sale" title="Vente" />} />
      <Route path="/catalogue/location" element={<SellingTypePage sellingType="rental" title="Location" />} />
      <Route path="/services" element={<ServicesCatalogPage />} />
      <Route path="/services/:serviceId" element={<ServiceDetailPage />} />
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
