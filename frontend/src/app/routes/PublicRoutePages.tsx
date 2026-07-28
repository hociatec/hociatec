import { lazyPage } from './routeLazy';

export const HomePage = lazyPage(() => import('@/features/home/pages/HomePage'), 'HomePage');
export const LoginPage = lazyPage(() => import('@/features/auth/pages/LoginPage'), 'LoginPage');
export const RegisterPage = lazyPage(
  () => import('@/features/auth/pages/RegisterPage'),
  'RegisterPage',
);
export const ForgotPasswordPage = lazyPage(
  () => import('@/features/auth/pages/ForgotPasswordPage'),
  'ForgotPasswordPage',
);
export const ResetPasswordPage = lazyPage(
  () => import('@/features/auth/pages/ResetPasswordPage'),
  'ResetPasswordPage',
);
export const ActivationPage = lazyPage(
  () => import('@/features/auth/pages/ActivationPage'),
  'ActivationPage',
);
export const ContactPage = lazyPage(
  () => import('@/features/contact/pages/ContactPage'),
  'ContactPage',
);
export const BetaTestPage = lazyPage(
  () => import('@/features/betaTest/pages/BetaTestPage'),
  'BetaTestPage',
);
export const BetaDashboardPage = lazyPage(() => import('@/features/betaTest/pages/BetaDashboardPage'), 'BetaDashboardPage');
export const BetaBugReportPage = lazyPage(() => import('@/features/betaTest/pages/BetaBugReportPage'), 'BetaBugReportPage');
export const BetaProfilePage = lazyPage(() => import('@/features/betaTest/pages/BetaProfilePage'), 'BetaProfilePage');
export const CguPage = lazyPage(() => import('@/features/legal/pages/CguPage'), 'CguPage');
export const CgvPage = lazyPage(() => import('@/features/legal/pages/CgvPage'), 'CgvPage');
export const PrivacyPage = lazyPage(
  () => import('@/features/legal/pages/PrivacyPage'),
  'PrivacyPage',
);
export const MentionsPage = lazyPage(
  () => import('@/features/legal/pages/MentionsPage'),
  'MentionsPage',
);
export const CategoryPage = lazyPage(
  () => import('@/features/catalog/pages/CategoryPage'),
  'CategoryPage',
);
export const ProductPage = lazyPage(
  () => import('@/features/catalog/pages/ProductPage'),
  'ProductPage',
);
export const SellingTypePage = lazyPage<
  import('@/features/catalog/pages/SellingTypePage').SellingTypePageProps
>(() => import('@/features/catalog/pages/SellingTypePage'), 'SellingTypePage');
export const CatalogSearchPage = lazyPage(
  () => import('@/features/catalog/pages/CatalogSearchPage'),
  'CatalogSearchPage',
);
export const GlobalSearchPage = lazyPage(
  () => import('@/features/search/pages/GlobalSearchPage'),
  'GlobalSearchPage',
);
export const NewsListPage = lazyPage(
  () => import('@/features/news/pages/NewsListPage'),
  'NewsListPage',
);
export const NewsDetailPage = lazyPage(
  () => import('@/features/news/pages/NewsDetailPage'),
  'NewsDetailPage',
);
export const CartPage = lazyPage(() => import('@/features/cart/pages/CartPage'), 'CartPage');
export const ServicesCatalogPage = lazyPage(
  () => import('@/features/quotes/pages/ServicesCatalogPage'),
  'ServicesCatalogPage',
);
export const ServiceDetailPage = lazyPage(
  () => import('@/features/quotes/pages/ServiceDetailPage'),
  'ServiceDetailPage',
);
export const TrainingsCatalogPage = lazyPage(
  () => import('@/features/trainings/pages/TrainingsCatalogPage'),
  'TrainingsCatalogPage',
);
export const TrainingDetailPage = lazyPage(
  () => import('@/features/trainings/pages/TrainingDetailPage'),
  'TrainingDetailPage',
);
export const CreateQuotePage = lazyPage(
  () => import('@/features/quotes/pages/CreateQuotePage'),
  'CreateQuotePage',
);
export const AppointmentBookingPage = lazyPage(
  () => import('@/features/appointments/pages/AppointmentBookingPage'),
  'AppointmentBookingPage',
);
export const TradeInPage = lazyPage(() => import('@/features/tradeIns/pages/TradeInPage'), 'TradeInPage');
