import { lazyPage } from './routeLazy';

export const ProfilePage = lazyPage(
  () => import('@/features/profile/pages/ProfilePage'),
  'ProfilePage',
);
export const CommunicationPreferencesPage = lazyPage(
  () => import('@/features/profile/pages/CommunicationPreferencesPage'),
  'CommunicationPreferencesPage',
);
export const ProfileAccessSessionsPage = lazyPage(
  () => import('@/features/profile/pages/ProfileAccessSessionsPage'),
  'ProfileAccessSessionsPage',
);
export const ClientDashboardPage = lazyPage(
  () => import('@/features/account/pages/ClientDashboardPage'),
  'ClientDashboardPage',
);
export const AddressesPage = lazyPage(
  () => import('@/features/addresses/pages/AddressesPage'),
  'AddressesPage',
);
export const MyTrainingsPage = lazyPage(
  () => import('@/features/trainings/pages/MyTrainingsPage'),
  'MyTrainingsPage',
);
export const MyTrainingDetailPage = lazyPage(
  () => import('@/features/trainings/pages/MyTrainingDetailPage'),
  'MyTrainingDetailPage',
);
export const MyQuotesPage = lazyPage(
  () => import('@/features/quotes/pages/MyQuotesPage'),
  'MyQuotesPage',
);
export const MyQuoteDetailPage = lazyPage(
  () => import('@/features/quotes/pages/MyQuoteDetailPage'),
  'MyQuoteDetailPage',
);
export const MyVouchersPage = lazyPage(
  () => import('@/features/vouchers/pages/MyVouchersPage'),
  'MyVouchersPage',
);
export const MyOrdersPage = lazyPage(
  () => import('@/features/orders/pages/MyOrdersPage'),
  'MyOrdersPage',
);
export const MySupportRequestsPage = lazyPage(
  () => import('@/features/support/pages/MySupportRequestsPage'),
  'MySupportRequestsPage',
);
export const OrderDetailPage = lazyPage(
  () => import('@/features/orders/pages/OrderDetailPage'),
  'OrderDetailPage',
);
export const CheckoutSuccessPage = lazyPage(
  () => import('@/features/orders/pages/CheckoutSuccessPage'),
  'CheckoutSuccessPage',
);
export const MyAppointmentsPage = lazyPage(
  () => import('@/features/appointments/pages/MyAppointmentsPage'),
  'MyAppointmentsPage',
);
export const MyRentalsPage = lazyPage(
  () => import('@/features/rentals/pages/MyRentalsPage'),
  'MyRentalsPage',
);
export const RequestAuditPage = lazyPage(
  () => import('@/features/audits/pages/RequestAuditPage'),
  'RequestAuditPage',
);
export const MyAuditsPage = lazyPage(
  () => import('@/features/audits/pages/MyAuditsPage'),
  'MyAuditsPage',
);
export const MyAuditDetailPage = lazyPage(
  () => import('@/features/audits/pages/MyAuditDetailPage'),
  'MyAuditDetailPage',
);
export const MyFavoritesPage = lazyPage(
  () => import('@/features/favorites/pages/MyFavoritesPage'),
  'MyFavoritesPage',
);
export const MyTradeInsPage = lazyPage(() => import('@/features/tradeIns/pages/MyTradeInsPage'), 'MyTradeInsPage');
