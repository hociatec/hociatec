import { expect, test, type Page } from '@playwright/test';

const PUBLIC_ROUTES = [
  '/',
  '/recherche?q=iphone',
  '/catalogue/recherche?q=iphone',
  '/catalogue/vente',
  '/catalogue/location',
  '/actualites',
  '/services',
  '/formations',
  '/login',
  '/register',
  '/forgot-password',
  '/contact',
  '/beta-test',
  '/legal/cgu',
  '/legal/cgv',
  '/legal/confidentialite',
  '/legal/mentions-legales',
  '/catalogue/recherche',
  '/recherche',
  '/panier',
  '/devis/nouveau',
  '/appointments/book',
  '/reprise',
  '/activation/test-token',
  '/reset-password/test-token',
];

const INTERNAL_ERROR_PATTERN =
  /Une erreur interne est survenue|La page n'a pas pu être affichée correctement/i;
const EXPECTED_500_API_PATTERNS = [
  '/api/public/catalog/products',
  '/api/public/catalog/categories',
] as const;

const isExpectedApiFailure = (entry: string) => {
  const [path] = entry.split(' ');
  return EXPECTED_500_API_PATTERNS.some((expected) => path.includes(expected));
};

const getUnexpectedApiFailures = (apiFailures: string[]) =>
  apiFailures.filter((entry) => !isExpectedApiFailure(entry));

const isExpectedConsoleError = (message: string, route: string) => {
  if (route.startsWith('/activation/')) {
    return /status code 400/i.test(message) || /Request failed with status code 400/i.test(message);
  }

  return /ResizeObserver loop/i.test(message);
};

const getUnexpectedConsoleErrors = (consoleErrors: string[], route: string) =>
  consoleErrors.filter((message) => !isExpectedConsoleError(message, route));

const expectNoUnexpectedErrors = (route: string, errors: ReturnType<typeof watchRuntimeErrors>) => {
  expect(getUnexpectedApiFailures(errors.apiFailures)).toEqual([]);
  expect(getUnexpectedConsoleErrors(errors.consoleErrors, route)).toEqual([]);
  expect(errors.pageErrors).toEqual([]);
};

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
    const url = response.url();
    if (url.includes('/api/') && response.status() >= 500) {
      apiFailures.push(`${response.status()} ${url}`);
    }
  });

  return { apiFailures, consoleErrors, pageErrors };
};

test('public home page renders without console errors', async ({ page }) => {
  const errors = watchRuntimeErrors(page);

  await page.goto('/');
  await expect(page).toHaveTitle(/Hociatec/i);
  await expect(page.locator('.site-header').first()).toBeVisible();
  await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
  expectNoUnexpectedErrors('/', errors);
});

for (const route of PUBLIC_ROUTES) {
  test(`public route ${route} renders without internal errors`, async ({ page }) => {
    const errors = watchRuntimeErrors(page);

    await page.goto(route, { waitUntil: 'networkidle' });
    await expect(page.locator('#root')).toBeAttached();
    await expect(page.locator('.site-header').first()).toBeVisible();
    await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
    expectNoUnexpectedErrors(route, errors);
  });
}

test('public product and news detail pages render from live API slugs', async ({ page, request }) => {
  const productsResponse = await request.get('/api/public/catalog/products?page=1&perPage=1&sort=created_desc');
  if (!productsResponse.ok()) test.skip(!productsResponse.ok(), 'API catalog unavailable');
  const productsPayload = await productsResponse.json();
  const productSlug = productsPayload.data?.items?.[0]?.slug;
  expect(typeof productSlug).toBe('string');

  const newsResponse = await request.get('/api/public/news?page=1&perPage=1');
  if (!newsResponse.ok()) test.skip(!newsResponse.ok(), 'API news unavailable');
  const newsPayload = await newsResponse.json();
  const newsSlug = newsPayload.data?.items?.[0]?.slug;
  expect(typeof newsSlug).toBe('string');

  for (const route of [`/catalogue/produits/${productSlug}`, `/actualites/${newsSlug}`]) {
    const errors = watchRuntimeErrors(page);

    await page.goto(route, { waitUntil: 'networkidle' });
    await expect(page.locator('#root')).toBeAttached();
    await expect(page.locator('.site-header').first()).toBeVisible();
    await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
    expectNoUnexpectedErrors(route, errors);
  }
});

test('public service, category and training pages render from live API slugs', async ({ page, request }) => {
  const categoryResponse = await request.get('/api/public/catalog/categories?page=1&perPage=1');
  if (!categoryResponse.ok()) test.skip(!categoryResponse.ok(), 'API categories unavailable');
  const categoryPayload = await categoryResponse.json();
  const categorySlug = categoryPayload.data?.items?.[0]?.slug;
  expect(typeof categorySlug).toBe('string');

  const servicesResponse = await request.get('/api/public/services?page=1&perPage=1');
  if (!servicesResponse.ok()) test.skip(!servicesResponse.ok(), 'API services unavailable');
  const servicesPayload = await servicesResponse.json();
  const serviceId = servicesPayload.data?.items?.[0]?.id;
  expect(typeof serviceId).toBe('number');

  const trainingResponse = await request.get('/api/public/trainings?page=1&perPage=1');
  if (!trainingResponse.ok()) test.skip(!trainingResponse.ok(), 'API trainings unavailable');
  const trainingPayload = await trainingResponse.json();
  const trainingSlug = trainingPayload.data?.items?.[0]?.slug;
  expect(typeof trainingSlug).toBe('string');

  for (const route of [
    `/catalogue/${categorySlug}`,
    `/services/${serviceId}`,
    `/formations/${trainingSlug}`,
  ]) {
    const errors = watchRuntimeErrors(page);

    await page.goto(route, { waitUntil: 'networkidle' });
    await expect(page.locator('#root')).toBeAttached();
    await expect(page.locator('.site-header').first()).toBeVisible();
    await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
    expectNoUnexpectedErrors(route, errors);
  }
});

test('@visual public home page keeps a stable first viewport', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveScreenshot('home-first-viewport.png', {
    fullPage: false,
    animations: 'disabled',
  });
});
