import { lazy } from 'react';

export const AdminLayout = lazy(() =>
  import('@/features/admin/layout/AdminLayout').then((module) => ({ default: module.AdminLayout })),
);
export const PrestationFormPage = lazy(() =>
  import('@/features/admin/appointments/pages/PrestationFormPage').then((module) => ({
    default: module.PrestationFormPage,
  })),
);
export const PrestationsListPage = lazy(() =>
  import('@/features/admin/appointments/pages/PrestationsListPage').then((module) => ({
    default: module.PrestationsListPage,
  })),
);
export const SchedulePage = lazy(() =>
  import('@/features/admin/appointments/pages/SchedulePage').then((module) => ({
    default: module.SchedulePage,
  })),
);
export const AdminDashboardPage = lazy(() =>
  import('@/features/admin/pages/AdminDashboardPage').then((module) => ({
    default: module.AdminDashboardPage,
  })),
);
export const AdminOperationsPage = lazy(() =>
  import('@/features/admin/operations/pages/AdminOperationsPage').then((module) => ({
    default: module.AdminOperationsPage,
  })),
);
export const AdminBackupsPage = lazy(() =>
  import('@/features/admin/backups/pages/AdminBackupsPage').then((module) => ({
    default: module.AdminBackupsPage,
  })),
);
export const AdminTrainingsPage = lazy(() =>
  import('@/features/admin/trainings/pages/AdminTrainingsPage').then((module) => ({
    default: module.AdminTrainingsPage,
  })),
);
export const TrainingFormPage = lazy(() =>
  import('@/features/admin/trainings/pages/TrainingFormPage').then((module) => ({
    default: module.TrainingFormPage,
  })),
);
export const TrainingSessionsPage = lazy(() =>
  import('@/features/admin/trainings/pages/TrainingSessionsPage').then((module) => ({
    default: module.TrainingSessionsPage,
  })),
);
export const TrainingSessionFormPage = lazy(() =>
  import('@/features/admin/trainings/pages/TrainingSessionFormPage').then((module) => ({
    default: module.TrainingSessionFormPage,
  })),
);
export const TrainingEnrollmentsPage = lazy(() =>
  import('@/features/admin/trainings/pages/TrainingEnrollmentsPage').then((module) => ({
    default: module.TrainingEnrollmentsPage,
  })),
);
export const TrainingCategoriesPage = lazy(() =>
  import('@/features/admin/trainings/pages/TrainingCategoriesPage').then((module) => ({
    default: module.TrainingCategoriesPage,
  })),
);
export const LoginPage = lazy(() =>
  import('@/features/auth/pages/LoginPage').then((module) => ({ default: module.LoginPage })),
);
export const RegisterPage = lazy(() =>
  import('@/features/auth/pages/RegisterPage').then((module) => ({ default: module.RegisterPage })),
);
export const ForgotPasswordPage = lazy(() =>
  import('@/features/auth/pages/ForgotPasswordPage').then((module) => ({ default: module.ForgotPasswordPage })),
);
export const ResetPasswordPage = lazy(() =>
  import('@/features/auth/pages/ResetPasswordPage').then((module) => ({ default: module.ResetPasswordPage })),
);
export const AppointmentBookingPage = lazy(() =>
  import('@/features/appointments/pages/AppointmentBookingPage').then((module) => ({
    default: module.AppointmentBookingPage,
  })),
);
export const MyAppointmentsPage = lazy(() =>
  import('@/features/appointments/pages/MyAppointmentsPage').then((module) => ({
    default: module.MyAppointmentsPage,
  })),
);
export const CategoriesListPage = lazy(() =>
  import('@/features/admin/catalog/pages/CategoriesListPage').then((module) => ({
    default: module.CategoriesListPage,
  })),
);
export const BrandsListPage = lazy(() =>
  import('@/features/admin/catalog/pages/BrandsListPage').then((module) => ({
    default: module.BrandsListPage,
  })),
);
export const BrandFormPage = lazy(() =>
  import('@/features/admin/catalog/pages/BrandFormPage').then((module) => ({
    default: module.BrandFormPage,
  })),
);
export const CategoryFormPage = lazy(() =>
  import('@/features/admin/catalog/pages/CategoryFormPage').then((module) => ({
    default: module.CategoryFormPage,
  })),
);
export const ProductsListPage = lazy(() =>
  import('@/features/admin/catalog/pages/ProductsListPage').then((module) => ({
    default: module.ProductsListPage,
  })),
);
export const ProductFormPage = lazy(() =>
  import('@/features/admin/catalog/pages/ProductFormPage').then((module) => ({
    default: module.ProductFormPage,
  })),
);
export const CategoryPage = lazy(() =>
  import('@/features/catalog/pages/CategoryPage').then((module) => ({
    default: module.CategoryPage,
  })),
);
export const ProductPage = lazy(() =>
  import('@/features/catalog/pages/ProductPage').then((module) => ({
    default: module.ProductPage,
  })),
);
export const SellingTypePage = lazy(() =>
  import('@/features/catalog/pages/SellingTypePage').then((module) => ({
    default: module.SellingTypePage,
  })),
);
export const CatalogSearchPage = lazy(() =>
  import('@/features/catalog/pages/CatalogSearchPage').then((module) => ({
    default: module.CatalogSearchPage,
  })),
);
export const GlobalSearchPage = lazy(() =>
  import('@/features/search/pages/GlobalSearchPage').then((module) => ({
    default: module.GlobalSearchPage,
  })),
);
export const CartPage = lazy(() =>
  import('@/features/cart/pages/CartPage').then((module) => ({ default: module.CartPage })),
);
export const HomePage = lazy(() =>
  import('@/features/home/pages/HomePage').then((module) => ({ default: module.HomePage })),
);
export const ProfilePage = lazy(() =>
  import('@/features/profile/pages/ProfilePage').then((module) => ({ default: module.ProfilePage })),
);
export const ClientDashboardPage = lazy(() =>
  import('@/features/trainings/pages/ClientDashboardPage').then((module) => ({
    default: module.ClientDashboardPage,
  })),
);
export const QuotesListPage = lazy(() =>
  import('@/features/admin/quotes/pages/QuotesListPage').then((module) => ({
    default: module.QuotesListPage,
  })),
);
export const AddressesPage = lazy(() =>
  import('@/features/addresses/pages/AddressesPage').then((module) => ({ default: module.AddressesPage })),
);
export const QuoteFormPage = lazy(() =>
  import('@/features/admin/quotes/pages/QuoteFormPage').then((module) => ({
    default: module.QuoteFormPage,
  })),
);
export const AdminQuoteDetailPage = lazy(() =>
  import('@/features/admin/quotes/pages/AdminQuoteDetailPage').then((module) => ({
    default: module.AdminQuoteDetailPage,
  })),
);
export const ServicesListPage = lazy(() =>
  import('@/features/admin/quotes/pages/ServicesListPage').then((module) => ({
    default: module.ServicesListPage,
  })),
);
export const ServiceFormPage = lazy(() =>
  import('@/features/admin/quotes/pages/ServiceFormPage').then((module) => ({
    default: module.ServiceFormPage,
  })),
);
export const CreateQuotePage = lazy(() =>
  import('@/features/quotes/pages/CreateQuotePage').then((module) => ({ default: module.CreateQuotePage })),
);
export const ServicesCatalogPage = lazy(() =>
  import('@/features/quotes/pages/ServicesCatalogPage').then((module) => ({ default: module.ServicesCatalogPage })),
);
export const ServiceDetailPage = lazy(() =>
  import('@/features/quotes/pages/ServiceDetailPage').then((module) => ({ default: module.ServiceDetailPage })),
);
export const TrainingsCatalogPage = lazy(() =>
  import('@/features/trainings/pages/TrainingsCatalogPage').then((module) => ({ default: module.TrainingsCatalogPage })),
);
export const TrainingDetailPage = lazy(() =>
  import('@/features/trainings/pages/TrainingDetailPage').then((module) => ({ default: module.TrainingDetailPage })),
);
export const MyTrainingsPage = lazy(() =>
  import('@/features/trainings/pages/MyTrainingsPage').then((module) => ({ default: module.MyTrainingsPage })),
);
export const MyTrainingDetailPage = lazy(() =>
  import('@/features/trainings/pages/MyTrainingDetailPage').then((module) => ({ default: module.MyTrainingDetailPage })),
);
export const MyQuotesPage = lazy(() =>
  import('@/features/quotes/pages/MyQuotesPage').then((module) => ({ default: module.MyQuotesPage })),
);
export const MyQuoteDetailPage = lazy(() =>
  import('@/features/quotes/pages/MyQuoteDetailPage').then((module) => ({ default: module.MyQuoteDetailPage })),
);
export const OrdersListPage = lazy(() =>
  import('@/features/admin/orders/pages/OrdersListPage').then((module) => ({
    default: module.OrdersListPage,
  })),
);
export const AdminOrderDetailPage = lazy(() =>
  import('@/features/admin/orders/pages/AdminOrderDetailPage').then((module) => ({
    default: module.AdminOrderDetailPage,
  })),
);
export const PaymentsListPage = lazy(() =>
  import('@/features/admin/payments/pages/PaymentsListPage').then((module) => ({
    default: module.PaymentsListPage,
  })),
);
export const PaymentDetailPage = lazy(() =>
  import('@/features/admin/payments/pages/PaymentDetailPage').then((module) => ({
    default: module.PaymentDetailPage,
  })),
);
export const AdminCustomersListPage = lazy(() =>
  import('@/features/admin/customers/pages/AdminCustomersListPage').then((module) => ({
    default: module.AdminCustomersListPage,
  })),
);
export const AdminLoyaltyPage = lazy(() =>
  import('@/features/admin/loyalty/pages/AdminLoyaltyPage').then((module) => ({
    default: module.AdminLoyaltyPage,
  })),
);
export const AdminCustomerDetailPage = lazy(() =>
  import('@/features/admin/customers/pages/AdminCustomerDetailPage').then((module) => ({
    default: module.AdminCustomerDetailPage,
  })),
);
export const AdminCustomerVoucherPage = lazy(() =>
  import('@/features/admin/customers/pages/AdminCustomerVoucherPage').then((module) => ({
    default: module.AdminCustomerVoucherPage,
  })),
);
export const MyVouchersPage = lazy(() =>
  import('@/features/vouchers/pages/MyVouchersPage').then((module) => ({ default: module.MyVouchersPage })),
);
export const MyOrdersPage = lazy(() =>
  import('@/features/orders/pages/MyOrdersPage').then((module) => ({ default: module.MyOrdersPage })),
);
export const OrderDetailPage = lazy(() =>
  import('@/features/orders/pages/OrderDetailPage').then((module) => ({ default: module.OrderDetailPage })),
);
export const CheckoutSuccessPage = lazy(() =>
  import('@/features/orders/pages/CheckoutSuccessPage').then((module) => ({ default: module.CheckoutSuccessPage })),
);
export const ContactPage = lazy(() =>
  import('@/features/contact/pages/ContactPage').then((module) => ({ default: module.ContactPage })),
);
export const ActivationPage = lazy(() =>
  import('@/features/auth/pages/ActivationPage').then((module) => ({ default: module.ActivationPage })),
);
export const RequestAuditPage = lazy(() =>
  import('@/features/audits/pages/RequestAuditPage').then((module) => ({
    default: module.RequestAuditPage,
  })),
);
export const MyAuditsPage = lazy(() =>
  import('@/features/audits/pages/MyAuditsPage').then((module) => ({ default: module.MyAuditsPage })),
);
export const MyAuditDetailPage = lazy(() =>
  import('@/features/audits/pages/MyAuditDetailPage').then((module) => ({
    default: module.MyAuditDetailPage,
  })),
);
export const AdminAuditsListPage = lazy(() =>
  import('@/features/admin/audits/pages/AdminAuditsListPage').then((module) => ({
    default: module.AdminAuditsListPage,
  })),
);
export const MarketingCampaignsPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingCampaignsPage').then((module) => ({
    default: module.MarketingCampaignsPage,
  })),
);
export const MarketingCampaignFormPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingCampaignFormPage').then((module) => ({
    default: module.MarketingCampaignFormPage,
  })),
);
export const MarketingTemplatesListPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingTemplatesListPage').then((module) => ({
    default: module.MarketingTemplatesListPage,
  })),
);
export const MarketingTemplateDetailPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingTemplateDetailPage').then((module) => ({
    default: module.MarketingTemplateDetailPage,
  })),
);
export const MarketingTemplateFormPage = lazy(() =>
  import('@/features/admin/marketing/pages/MarketingTemplateFormPage').then((module) => ({
    default: module.MarketingTemplateFormPage,
  })),
);
export const PromotionsListPage = lazy(() =>
  import('@/features/admin/promotions/pages/PromotionsListPage').then((module) => ({
    default: module.PromotionsListPage,
  })),
);
export const PromotionFormPage = lazy(() =>
  import('@/features/admin/promotions/pages/PromotionFormPage').then((module) => ({
    default: module.PromotionFormPage,
  })),
);
export const VouchersPage = lazy(() =>
  import('@/features/admin/vouchers/pages/VouchersPage').then((module) => ({
    default: module.VouchersPage,
  })),
);
export const VoucherFormPage = lazy(() =>
  import('@/features/admin/vouchers/pages/VoucherFormPage').then((module) => ({
    default: module.VoucherFormPage,
  })),
);
export const AdminAuditDetailPage = lazy(() =>
  import('@/features/admin/audits/pages/AdminAuditDetailPage').then((module) => ({
    default: module.AdminAuditDetailPage,
  })),
);
export const CguPage = lazy(() =>
  import('@/features/legal/pages/CguPage').then((module) => ({ default: module.CguPage })),
);
export const CgvPage = lazy(() =>
  import('@/features/legal/pages/CgvPage').then((module) => ({ default: module.CgvPage })),
);
export const PrivacyPage = lazy(() =>
  import('@/features/legal/pages/PrivacyPage').then((module) => ({ default: module.PrivacyPage })),
);
export const MentionsPage = lazy(() =>
  import('@/features/legal/pages/MentionsPage').then((module) => ({ default: module.MentionsPage })),
);
export const MyFavoritesPage = lazy(() =>
  import('@/features/favorites/pages/MyFavoritesPage').then((module) => ({
    default: module.MyFavoritesPage,
  })),
);
