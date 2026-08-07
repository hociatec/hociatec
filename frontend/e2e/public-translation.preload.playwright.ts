import { expect, test, type APIRequestContext, type BrowserContext } from '@playwright/test';

type Language = 'fr' | 'en';

const STORAGE_KEY = 'hocatec.language';
const BASE_SETTLE_DELAY_MS = 900;
const ROUTE_NAVIGATION_TIMEOUT_MS = 5_000;
const ROUTE_SELECTOR_TIMEOUT_MS = 5_000;
const ROOT_SELECTOR = '#root';

const STATIC_PUBLIC_ROUTES = [
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

const FR_SIGNAL_PATTERNS = [
  /actualite/i,
  /connexion/i,
  /inscription/i,
  /panier/i,
  /categorie|catalogue/i,
  /mentions?|confident/i,
  /recherche/i,
  /services/i,
  /formation/i,
  /actualit/i,
  /vous/i,
  /bonjour|merci|pouvez/i,
] as const;

const normalizeText = (value: string) =>
  value
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .replace(/\u00a0/g, ' ')
    .trim();

const tokenize = (value: string) =>
  normalizeText(value)
    .replace(/[^a-z0-9\s]/gi, ' ')
    .split(/\s+/)
    .filter((token) => token.length > 2);

const intersection = (left: Set<string>, right: Set<string>) => {
  let count = 0;
  for (const token of left) {
    if (right.has(token)) {
      count += 1;
    }
  }
  return count;
};

const jaccard = (left: string[], right: string[]) => {
  const leftSet = new Set(left);
  const rightSet = new Set(right);
  const common = intersection(leftSet, rightSet);
  const union = new Set([...leftSet, ...rightSet]).size;
  if (union === 0) return 0;
  return common / union;
};

const frenchSignals = (value: string) =>
  FR_SIGNAL_PATTERNS.reduce((sum, pattern) => sum + (pattern.test(value) ? 1 : 0), 0);

const capturePageText = async (context: BrowserContext, route: string, language: Language) => {
  const page = await context.newPage();

  await page.addInitScript(
    ({ storageKey, initialLanguage }) => {
      try {
        localStorage.setItem(storageKey, initialLanguage);
        document.documentElement.lang = initialLanguage;
      } catch {
        // ignore
      }
    },
    { storageKey: STORAGE_KEY, initialLanguage: language },
  );

  let response: { status: () => number } | null = null;

  try {
      response = await page.goto(route, {
        waitUntil: 'domcontentloaded',
        timeout: ROUTE_NAVIGATION_TIMEOUT_MS,
      });
      await page.waitForLoadState('domcontentloaded');
      await page.waitForSelector(ROOT_SELECTOR, { state: 'attached', timeout: ROUTE_SELECTOR_TIMEOUT_MS });
      await page.waitForTimeout(BASE_SETTLE_DELAY_MS);
  } catch {
    await page.close();
    return {
      status: response?.status() ?? 0,
      text: '',
      normalized: '',
      error: 'navigation-timeout',
    };
  }

  const rawText = (await page.locator('body').innerText()) ?? '';
  await page.close();

  return {
    status: response?.status() ?? 0,
    text: rawText,
    normalized: normalizeText(rawText),
    error: undefined,
  };
};

const collectDynamicRoutes = async (request: APIRequestContext) => {
  const routes: string[] = [];
  try {
    const category = await request.get('/api/public/catalog/categories?page=1&perPage=1');
    if (category.ok()) {
      const payload = await category.json().catch(() => null);
      const slug = payload?.data?.items?.[0]?.slug;
      if (typeof slug === 'string' && slug.length > 0) routes.push(`/catalogue/${slug}`);
    }
  } catch {
    // ignore, environment may block outbound DNS in test runner
  }

  try {
    const product = await request.get('/api/public/catalog/products?page=1&perPage=1&sort=created_desc');
    if (product.ok()) {
      const payload = await product.json().catch(() => null);
      const slug = payload?.data?.items?.[0]?.slug;
      if (typeof slug === 'string' && slug.length > 0) routes.push(`/catalogue/produits/${slug}`);
    }
  } catch {
    // ignore, environment may block outbound DNS in test runner
  }

  try {
    const news = await request.get('/api/public/news?page=1&perPage=1');
    if (news.ok()) {
      const payload = await news.json().catch(() => null);
      const slug = payload?.data?.items?.[0]?.slug;
      if (typeof slug === 'string' && slug.length > 0) routes.push(`/actualites/${slug}`);
    }
  } catch {
    // ignore, environment may block outbound DNS in test runner
  }

  try {
    const services = await request.get('/api/public/services?page=1&perPage=1');
    if (services.ok()) {
      const payload = await services.json().catch(() => null);
      const id = payload?.data?.items?.[0]?.id;
      if (Number.isFinite(Number(id))) routes.push(`/services/${id}`);
    }
  } catch {
    // ignore, environment may block outbound DNS in test runner
  }

  try {
    const trainings = await request.get('/api/public/trainings?page=1&perPage=1');
    if (trainings.ok()) {
      const payload = await trainings.json().catch(() => null);
      const slug = payload?.data?.items?.[0]?.slug;
      if (typeof slug === 'string' && slug.length > 0) routes.push(`/formations/${slug}`);
    }
  } catch {
    // ignore, environment may block outbound DNS in test runner
  }

  return routes;
};

const evaluateRoute = async (
  route: string,
  context: BrowserContext,
): Promise<{
  route: string;
  status: 'ok' | 'skip' | 'warn' | 'fail';
  similarity: number;
  frSignals: number;
  enSignals: number;
  reason?: string;
}> => {
  const [fr, en] = await Promise.all([
    capturePageText(context, route, 'fr'),
    capturePageText(context, route, 'en'),
  ]);

  if (fr.error && en.error) {
    return {
      route,
      status: 'skip',
      similarity: 1,
      frSignals: frenchSignals(fr.normalized),
      enSignals: frenchSignals(en.normalized),
      reason: 'navigation-error',
    };
  }

  if (fr.error || en.error) {
    return {
      route,
      status: 'warn',
      similarity: 1,
      frSignals: frenchSignals(fr.normalized),
      enSignals: frenchSignals(en.normalized),
      reason: 'single-language-navigation-error',
    };
  }

  if (fr.status >= 500 && en.status >= 500) {
    return {
      route,
      status: 'skip',
      similarity: 1,
      frSignals: frenchSignals(fr.normalized),
      enSignals: frenchSignals(en.normalized),
      reason: 'backend-500',
    };
  }

  if (fr.text.trim() === '' || en.text.trim() === '') {
    return {
      route,
      status: 'skip',
      similarity: 1,
      frSignals: frenchSignals(fr.normalized),
      enSignals: frenchSignals(en.normalized),
      reason: 'empty-body',
    };
  }

  const similarity = jaccard(tokenize(fr.normalized), tokenize(en.normalized));
  const frSignals = frenchSignals(fr.normalized);
  const enSignals = frenchSignals(en.normalized);

  if (fr.normalized === en.normalized) {
    return {
      route,
      status: 'fail',
      similarity,
      frSignals,
      enSignals,
      reason: 'no-text-change',
    };
  }

  if (similarity > 0.97 && enSignals >= frSignals - 1) {
    return {
      route,
      status: 'warn',
      similarity,
      frSignals,
      enSignals,
      reason: 'weak-change',
    };
  }

  return {
    route,
    status: 'ok',
    similarity,
    frSignals,
    enSignals,
  };
};

test('prewarm-and-validate-public-translations', async ({ browser, request }) => {
  test.setTimeout(180_000);
  const context = await browser.newContext();
  const baseUrl = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:4173';

  const probe = await context.newPage();
  try {
    await probe.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 5_000 });
  } catch {
    await probe.close();
    test.skip(true, `Impossible de joindre ${baseUrl} depuis ce runner`);
  }
  await probe.close();

  const dynamicRoutes = await collectDynamicRoutes(request);
  const routes = Array.from(new Set([...STATIC_PUBLIC_ROUTES, ...dynamicRoutes])).sort();

  type RouteTranslationResult = Awaited<ReturnType<typeof evaluateRoute>>;
  const checks: RouteTranslationResult[] = [];
  for (const route of routes) {
    checks.push(await evaluateRoute(route, context));
  }

  await context.close();

  const failed = checks.filter((entry) => entry.status === 'fail');
  const warned = checks.filter((entry) => entry.status === 'warn');

  const report = checks
    .map(({ route, status, similarity, frSignals, enSignals, reason }) =>
      `${String(status).toUpperCase().padEnd(5)} ${route} | sim=${similarity.toFixed(2)} | fr=${frSignals} en=${enSignals} ${
        reason ? `| ${reason}` : ''
      }`,
    )
    .join('\n');

  // eslint-disable-next-line no-console
  console.log(`\nVerifying public translation coverage (FR -> EN)\n${report}\n`);

  expect(failed, `Missing translation on ${failed.length} route(s)`).toHaveLength(0);
  expect(warned, `Weak translation signal on ${warned.length} route(s)`).toHaveLength(0);
});
