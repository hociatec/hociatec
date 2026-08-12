import { lazyPage } from '../routeLazy';

export const AdminCustomersListPage = lazyPage(
  () => import('@/features/admin/customers/pages/AdminCustomersListPage'),
  'AdminCustomersListPage',
);
export const AdminCustomerDetailPage = lazyPage(
  () => import('@/features/admin/customers/pages/AdminCustomerDetailPage'),
  'AdminCustomerDetailPage',
);
export const AdminCustomerVoucherPage = lazyPage(
  () => import('@/features/admin/customers/pages/AdminCustomerVoucherPage'),
  'AdminCustomerVoucherPage',
);
export const AdminLoyaltyPage = lazyPage(
  () => import('@/features/admin/loyalty/pages/AdminLoyaltyPage'),
  'AdminLoyaltyPage',
);
export const AdminSupportRequestsListPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminSupportRequestsListPage'),
  'AdminSupportRequestsListPage',
);
export const AdminSupportRequestCreatePage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminSupportRequestCreatePage'),
  'AdminSupportRequestCreatePage',
);
export const AdminSupportRequestDetailPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminSupportRequestDetailPage'),
  'AdminSupportRequestDetailPage',
);
export const AdminRefundRequestsListPage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminRefundRequestsListPage'),
  'AdminRefundRequestsListPage',
);
export const AdminRefundRequestCreatePage = lazyPage(
  () => import('@/features/admin/operations/pages/AdminRefundRequestCreatePage'),
  'AdminRefundRequestCreatePage',
);
