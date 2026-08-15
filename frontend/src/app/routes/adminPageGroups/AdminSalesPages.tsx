import { lazyPage } from '../routeLazy';

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
export const AdminFulfillmentOperationsPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminFulfillmentOperationsPage'),
  'AdminFulfillmentOperationsPage',
);
export const AdminRentalsListPage = lazyPage(
  () => import('@/features/admin/rentals/pages/AdminRentalsListPage'),
  'AdminRentalsListPage',
);
export const AdminRentalDetailPage = lazyPage(
  () => import('@/features/admin/rentals/pages/AdminRentalDetailPage'),
  'AdminRentalDetailPage',
);
