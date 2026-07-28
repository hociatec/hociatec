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
