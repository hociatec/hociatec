import { test } from '@playwright/test';

import { ADMIN_STORAGE_STATE } from './helpers/auth';
import { expectHealthyPage } from './helpers/site';

test.use({ storageState: ADMIN_STORAGE_STATE });

const ADMIN_ROUTES = [
  '/admin',
  '/admin/exports',
  '/admin/backups',
  '/admin/ui-catalog',
  '/admin/trainings',
  '/admin/trainings/new',
  '/admin/trainings/sessions',
  '/admin/trainings/sessions/new',
  '/admin/trainings/enrollments',
  '/admin/trainings/categories',
  '/admin/appointments/motifs',
  '/admin/appointments/motifs/new',
  '/admin/appointments/schedule',
  '/admin/catalog/categories',
  '/admin/catalog/categories/new',
  '/admin/catalog/brands',
  '/admin/catalog/brands/new',
  '/admin/catalog/products',
  '/admin/catalog/products/new',
  '/admin/catalog/stock',
  '/admin/quotes',
  '/admin/quotes/new',
  '/admin/services',
  '/admin/services/new',
  '/admin/orders',
  '/admin/orders/fulfillment',
  '/admin/orders/bulk-actions',
  '/admin/payments',
  '/admin/customers',
  '/admin/customers/support',
  '/admin/customers/support/new',
  '/admin/customers/refunds',
  '/admin/customers/refunds/new',
  '/admin/loyalty',
  '/admin/news',
  '/admin/news/new',
  '/admin/marketing',
  '/admin/marketing/new',
  '/admin/marketing/templates',
  '/admin/marketing/templates/new',
  '/admin/transactional-emails',
  '/admin/transactional-emails/logs',
  '/admin/transactional-emails/new',
  '/admin/promotions',
  '/admin/promotions/new',
  '/admin/vouchers',
  '/admin/vouchers/new',
  '/admin/audits',
  '/admin/beta-testers',
  '/admin/beta-campaigns',
  '/admin/beta-reports',
  '/admin/trade-ins',
] as const;

for (const route of ADMIN_ROUTES) {
  test(`admin route ${route} renders without runtime failures`, async ({ page }) => {
    await expectHealthyPage(page, route);
  });
}
