import { test } from '@playwright/test';

import { CLIENT_STORAGE_STATE } from './helpers/auth';
import { expectHealthyPage } from './helpers/site';

test.use({ storageState: CLIENT_STORAGE_STATE });

const CLIENT_ROUTES = [
  '/mon-espace',
  '/profile',
  '/profile/communication-preferences',
  '/profile/addresses',
  '/favorites',
  '/quotes/me',
  '/orders/me',
  '/vouchers/me',
  '/trainings/me',
  '/appointments/me',
  '/audits/request',
  '/audits/me',
  '/reprises',
] as const;

for (const route of CLIENT_ROUTES) {
  test(`client route ${route} renders without runtime failures`, async ({ page }) => {
    await expectHealthyPage(page, route);
  });
}
