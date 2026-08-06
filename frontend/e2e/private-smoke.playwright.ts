import { expect, test, type Page } from '@playwright/test';

const PROTECTED_ROUTES = [
  '/quotes/me',
  '/quotes/me/123',
  '/orders/me',
  '/vouchers/me',
  '/trainings/me',
  '/trainings/me/456',
  '/orders/789',
  '/checkout/success',
  '/mon-espace',
  '/profile',
  '/profile/communication-preferences',
  '/favorites',
  '/reprises',
  '/profile/addresses',
  '/appointments/me',
  '/audits/request',
  '/audits/me',
  '/audits/me/321',
  '/appointments/admin',
] as const;

const ADMIN_ROUTES = [
  '/admin',
  '/admin/operations',
  '/admin/backups',
  '/admin/trainings',
  '/admin/trainings/new',
  '/admin/trainings/1/edit',
  '/admin/appointments/motifs',
  '/admin/appointments/schedule',
  '/admin/catalog/categories',
  '/admin/catalog/categories/new',
  '/admin/catalog/categories/1/edit',
  '/admin/catalog/brands',
  '/admin/catalog/brands/new',
  '/admin/catalog/brands/1/edit',
  '/admin/catalog/products',
  '/admin/catalog/products/new',
  '/admin/catalog/products/1/edit',
  '/admin/quotes',
  '/admin/quotes/new',
  '/admin/quotes/1',
  '/admin/quotes/1/edit',
  '/admin/services',
  '/admin/services/new',
  '/admin/services/1/edit',
  '/admin/orders',
  '/admin/orders/1',
  '/admin/payments',
  '/admin/payments/1',
  '/admin/customers',
  '/admin/customers/1',
  '/admin/customers/1/vouchers/new',
  '/admin/news',
  '/admin/news/new',
  '/admin/news/1/edit',
  '/admin/marketing',
  '/admin/marketing/new',
  '/admin/promotions',
  '/admin/promotions/new',
  '/admin/vouchers',
  '/admin/vouchers/new',
  '/admin/audits',
  '/admin/audits/1',
  '/admin/beta-testers',
  '/admin/beta-campaigns',
  '/admin/beta-reports',
  '/admin/trade-ins',
] as const;

const ROUTE_LIST = [...PROTECTED_ROUTES, ...ADMIN_ROUTES];

const INTERNAL_ERROR_PATTERN = /Une erreur interne est survenue|La page n'a pas pu être affichée correctement/i;

const isExpectedConsoleError = (message: string) => /ResizeObserver loop/i.test(message);

const watchRuntimeErrors = (page: Page) => {
  const consoleErrors: string[] = [];
  const pageErrors: string[] = [];
  const apiFailures: string[] = [];

  page.on('console', (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  });

  page.on('pageerror', (error) => {
    pageErrors.push(error.message);
  });

  page.on('response', (response) => {
    if (response.url().includes('/api/') && response.status() >= 500) {
      apiFailures.push(`${response.status()} ${response.url()}`);
    }
  });

  return { consoleErrors, pageErrors, apiFailures };
};

const expectNoUnexpectedErrors = (errors: ReturnType<typeof watchRuntimeErrors>) => {
  const unexpectedApiFailures = errors.apiFailures;
  const unexpectedConsoleErrors = errors.consoleErrors.filter(
    (message) => !isExpectedConsoleError(message),
  );

  expect(unexpectedApiFailures).toEqual([]);
  expect(unexpectedConsoleErrors).toEqual([]);
  expect(errors.pageErrors).toEqual([]);
};

test('private routes redirect to authentication entry points and render cleanly', async ({ page }) => {
  for (const route of ROUTE_LIST) {
    const errors = watchRuntimeErrors(page);

    await test.step(`route ${route}`, async () => {
      await page.goto(route, { waitUntil: 'domcontentloaded' });

      await expect(page.locator('#root')).toBeAttached();
      await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
      await expect(page).toHaveURL(/\/login|\/$/);

      expectNoUnexpectedErrors(errors);
    });
  }
});
