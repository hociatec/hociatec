import { Navigate, Route, Routes } from 'react-router-dom';

import { AdminLayout } from '@/features/admin/layout/AdminLayout';
import { PrestationFormPage } from '@/features/admin/appointments/pages/PrestationFormPage';
import { PrestationsListPage } from '@/features/admin/appointments/pages/PrestationsListPage';
import { SchedulePage } from '@/features/admin/appointments/pages/SchedulePage';
import { AdminDashboardPage } from '@/features/admin/pages/AdminDashboardPage';
import { ProtectedRoute } from '@/features/auth/components/ProtectedRoute';
import { LoginPage } from '@/features/auth/pages/LoginPage';
import { RegisterPage } from '@/features/auth/pages/RegisterPage';
import { AppointmentBookingPage } from '@/features/appointments/pages/AppointmentBookingPage';
import { MyAppointmentsPage } from '@/features/appointments/pages/MyAppointmentsPage';
import { CategoriesListPage } from '@/features/admin/catalog/pages/CategoriesListPage';
import { CategoryFormPage } from '@/features/admin/catalog/pages/CategoryFormPage';
import { ProductsListPage } from '@/features/admin/catalog/pages/ProductsListPage';
import { ProductFormPage } from '@/features/admin/catalog/pages/ProductFormPage';
import { CategoryPage } from '@/features/catalog/pages/CategoryPage';
import { ProductPage } from '@/features/catalog/pages/ProductPage';
import { SellingTypePage } from '@/features/catalog/pages/SellingTypePage';
import { CartPage } from '@/features/cart/pages/CartPage';
import { HomePage } from '@/features/home/pages/HomePage';
import { ProfilePage } from '@/features/profile/pages/ProfilePage';
import { QuotesListPage } from '@/features/admin/quotes/pages/QuotesListPage';
import { AddressesPage } from '@/features/addresses/pages/AddressesPage';
import { QuoteFormPage } from '@/features/admin/quotes/pages/QuoteFormPage';
import { ServicesListPage } from '@/features/admin/quotes/pages/ServicesListPage';
import { ServiceFormPage } from '@/features/admin/quotes/pages/ServiceFormPage';
import { CreateQuotePage } from '@/features/quotes/pages/CreateQuotePage';
import { MyQuotesPage } from '@/features/quotes/pages/MyQuotesPage';
import { OrdersListPage } from '@/features/admin/orders/pages/OrdersListPage';
import { MyOrdersPage } from '@/features/orders/pages/MyOrdersPage';
import { OrderDetailPage } from '@/features/orders/pages/OrderDetailPage';
import { ContactPage } from '@/features/contact/pages/ContactPage';
import { ActivationPage } from '@/features/auth/pages/ActivationPage';
import { RequestAuditPage } from '@/features/audits/pages/RequestAuditPage';
import { MyAuditsPage } from '@/features/audits/pages/MyAuditsPage';
import { MyAuditDetailPage } from '@/features/audits/pages/MyAuditDetailPage';
import { AdminAuditsListPage } from '@/features/admin/audits/pages/AdminAuditsListPage';
import { AdminAuditDetailPage } from '@/features/admin/audits/pages/AdminAuditDetailPage';
import { CguPage } from '@/features/legal/pages/CguPage';
import { CgvPage } from '@/features/legal/pages/CgvPage';
import { PrivacyPage } from '@/features/legal/pages/PrivacyPage';
import { MentionsPage } from '@/features/legal/pages/MentionsPage';

export const AppRoutes = () => (
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
);
