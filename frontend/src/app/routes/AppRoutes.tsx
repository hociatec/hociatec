import { lazy, Suspense } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';

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
const LoginPage = lazy(() =>
  import('@/features/auth/pages/LoginPage').then((module) => ({ default: module.LoginPage })),
);
const RegisterPage = lazy(() =>
  import('@/features/auth/pages/RegisterPage').then((module) => ({ default: module.RegisterPage })),
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
const CartPage = lazy(() =>
  import('@/features/cart/pages/CartPage').then((module) => ({ default: module.CartPage })),
);
const HomePage = lazy(() =>
  import('@/features/home/pages/HomePage').then((module) => ({ default: module.HomePage })),
);
const ProfilePage = lazy(() =>
  import('@/features/profile/pages/ProfilePage').then((module) => ({ default: module.ProfilePage })),
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
const MyQuotesPage = lazy(() =>
  import('@/features/quotes/pages/MyQuotesPage').then((module) => ({ default: module.MyQuotesPage })),
);
const OrdersListPage = lazy(() =>
  import('@/features/admin/orders/pages/OrdersListPage').then((module) => ({
    default: module.OrdersListPage,
  })),
);
const MyOrdersPage = lazy(() =>
  import('@/features/orders/pages/MyOrdersPage').then((module) => ({ default: module.MyOrdersPage })),
);
const OrderDetailPage = lazy(() =>
  import('@/features/orders/pages/OrderDetailPage').then((module) => ({ default: module.OrderDetailPage })),
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
      <Route path="/contact" element={<ContactPage />} />
      <Route path="/legal/cgu" element={<CguPage />} />
      <Route path="/legal/cgv" element={<CgvPage />} />
      <Route path="/legal/confidentialite" element={<PrivacyPage />} />
      <Route path="/legal/mentions-legales" element={<MentionsPage />} />
      <Route path="/activation/:token" element={<ActivationPage />} />
      <Route path="/catalogue/produits/:slug" element={<ProductPage />} />
      <Route path="/catalogue/vente" element={<SellingTypePage sellingType="sale" title="Vente" />} />
      <Route path="/catalogue/location" element={<SellingTypePage sellingType="rental" title="Location" />} />
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
        path="/orders/me"
        element={
          <ProtectedRoute>
            <MyOrdersPage />
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
            <AdminLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<AdminDashboardPage />} />
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
          <Route path="products">
            <Route index element={<ProductsListPage />} />
            <Route path="new" element={<ProductFormPage />} />
            <Route path=":productId/edit" element={<ProductFormPage />} />
          </Route>
        </Route>
        <Route path="quotes">
          <Route index element={<QuotesListPage />} />
          <Route path=":quoteId/edit" element={<QuoteFormPage />} />
          <Route path="services">
            <Route index element={<ServicesListPage />} />
            <Route path="new" element={<ServiceFormPage />} />
            <Route path=":serviceId/edit" element={<ServiceFormPage />} />
          </Route>
        </Route>
        <Route path="orders">
          <Route index element={<OrdersListPage />} />
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
