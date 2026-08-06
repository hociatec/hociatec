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
  expect(errors).toEqual({ apiFailures: [], consoleErrors: [], pageErrors: [] });
});

for (const route of PUBLIC_ROUTES) {
  test(`public route ${route} renders without internal errors`, async ({ page }) => {
    const errors = watchRuntimeErrors(page);

    await page.goto(route, { waitUntil: 'networkidle' });
    await expect(page.locator('#root')).toBeAttached();
    await expect(page.locator('.site-header').first()).toBeVisible();
    await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
    expect(errors).toEqual({ apiFailures: [], consoleErrors: [], pageErrors: [] });
  });
}

test('public product and news detail pages render from live API slugs', async ({ page, request }) => {
  const productsResponse = await request.get('/api/public/catalog/products?page=1&perPage=1&sort=created_desc');
  expect(productsResponse.ok()).toBe(true);
  const productsPayload = await productsResponse.json();
  const productSlug = productsPayload.data?.items?.[0]?.slug;
  expect(typeof productSlug).toBe('string');

  const newsResponse = await request.get('/api/public/news?page=1&perPage=1');
  expect(newsResponse.ok()).toBe(true);
  const newsPayload = await newsResponse.json();
  const newsSlug = newsPayload.data?.items?.[0]?.slug;
  expect(typeof newsSlug).toBe('string');

  for (const route of [`/catalogue/produits/${productSlug}`, `/actualites/${newsSlug}`]) {
    const errors = watchRuntimeErrors(page);

    await page.goto(route, { waitUntil: 'networkidle' });
    await expect(page.locator('#root')).toBeAttached();
    await expect(page.locator('.site-header').first()).toBeVisible();
    await expect(page.getByText(INTERNAL_ERROR_PATTERN)).toHaveCount(0);
    expect(errors).toEqual({ apiFailures: [], consoleErrors: [], pageErrors: [] });
  }
});

test('public service, category and training pages render from live API slugs', async ({ page, request }) => {
  const categoryResponse = await request.get('/api/public/catalog/categories?page=1&perPage=1');
  expect(categoryResponse.ok()).toBe(true);
  const categoryPayload = await categoryResponse.json();
  const categorySlug = categoryPayload.data?.items?.[0]?.slug;
  expect(typeof categorySlug).toBe('string');

  const servicesResponse = await request.get('/api/public/services?page=1&perPage=1');
  expect(servicesResponse.ok()).toBe(true);
  const servicesPayload = await servicesResponse.json();
  const serviceId = servicesPayload.data?.items?.[0]?.id;
  expect(typeof serviceId).toBe('number');

  const trainingResponse = await request.get('/api/public/trainings?page=1&perPage=1');
  expect(trainingResponse.ok()).toBe(true);
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
    expect(errors).toEqual({ apiFailures: [], consoleErrors: [], pageErrors: [] });
  }
});

test('@visual public home page keeps a stable first viewport', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveScreenshot('home-first-viewport.png', {
    fullPage: false,
    animations: 'disabled',
  });
});
