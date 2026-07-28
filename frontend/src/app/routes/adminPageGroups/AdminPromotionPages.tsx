import { lazyPage } from '../routeLazy';

export const PromotionsListPage = lazyPage(
  () => import('@/features/admin/promotions/pages/PromotionsListPage'),
  'PromotionsListPage',
);
export const PromotionFormPage = lazyPage(
  () => import('@/features/admin/promotions/pages/PromotionFormPage'),
  'PromotionFormPage',
);
export const VouchersPage = lazyPage(
  () => import('@/features/admin/vouchers/pages/VouchersPage'),
  'VouchersPage',
);
export const VoucherFormPage = lazyPage(
  () => import('@/features/admin/vouchers/pages/VoucherFormPage'),
  'VoucherFormPage',
);
