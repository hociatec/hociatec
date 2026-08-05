import fs from 'node:fs/promises';
import path from 'node:path';

const SITE_URL = 'https://hociatec.fr';
const DEFAULT_API_BASE_URL = 'https://api.hociatec.fr';
const FETCH_TIMEOUT_MS = Number(process.env.SITEMAP_FETCH_TIMEOUT_MS ?? 10000);
const ENABLE_DYNAMIC_SITEMAP =
  process.env.SITEMAP_DYNAMIC === '1' ||
  Boolean(process.env.SITEMAP_API_BASE_URL || process.env.VITE_API_BASE_URL);
const REQUIRE_DYNAMIC_SITEMAP = process.env.SITEMAP_REQUIRE_DYNAMIC === '1';

interface ApiResponse<T> {
  status: 'success' | 'error';
  data: T;
  message?: string;
}

interface CategoryDto {
  slug: string;
  updatedAt?: string | null;
}

interface ProductDto {
  slug: string;
  updatedAt?: string | null;
}

interface ProductListPayload {
  items: ProductDto[];
  meta?: {
    page: number;
    totalPages: number;
  };
}

interface ServiceDto {
  id: number;
}

interface TrainingDto {
  slug: string;
}

interface SitemapEntry {
  loc: string;
  lastmod?: string;
  changefreq?: 'daily' | 'weekly' | 'monthly' | 'yearly';
  priority?: string;
}

const staticEntries: SitemapEntry[] = [
  { loc: '/', changefreq: 'weekly', priority: '1.0' },
  { loc: '/catalogue/vente', changefreq: 'daily', priority: '0.9' },
  { loc: '/catalogue/location', changefreq: 'daily', priority: '0.9' },
  { loc: '/catalogue/recherche', changefreq: 'weekly', priority: '0.7' },
  { loc: '/services', changefreq: 'weekly', priority: '0.8' },
  { loc: '/formations', changefreq: 'weekly', priority: '0.8' },
  { loc: '/appointments/book', changefreq: 'monthly', priority: '0.7' },
  { loc: '/devis/nouveau', changefreq: 'monthly', priority: '0.7' },
  { loc: '/contact', changefreq: 'monthly', priority: '0.6' },
  { loc: '/audits/request', changefreq: 'monthly', priority: '0.6' },
  { loc: '/legal/cgu', changefreq: 'yearly', priority: '0.3' },
  { loc: '/legal/cgv', changefreq: 'yearly', priority: '0.3' },
  { loc: '/legal/confidentialite', changefreq: 'yearly', priority: '0.3' },
  { loc: '/legal/mentions-legales', changefreq: 'yearly', priority: '0.3' },
];

const resolveApiBaseUrl = () =>
  process.env.SITEMAP_API_BASE_URL ||
  process.env.VITE_API_BASE_URL ||
  DEFAULT_API_BASE_URL;

const normalizeBaseUrl = (value: string) => value.replace(/\/+$/u, '');

const escapeXml = (value: string) =>
  value
    .replace(/&/gu, '&amp;')
    .replace(/</gu, '&lt;')
    .replace(/>/gu, '&gt;')
    .replace(/"/gu, '&quot;')
    .replace(/'/gu, '&apos;');

const toAbsoluteUrl = (pathOrUrl: string) =>
  pathOrUrl.startsWith('http')
    ? pathOrUrl
    : `${SITE_URL}${pathOrUrl.startsWith('/') ? pathOrUrl : `/${pathOrUrl}`}`;

const toLastmod = (value?: string | null) => {
  if (!value) return undefined;

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return undefined;

  return date.toISOString().slice(0, 10);
};

const isApiResponse = <T>(payload: ApiResponse<T> | T): payload is ApiResponse<T> =>
  Boolean(
    payload &&
      typeof payload === 'object' &&
      'status' in payload &&
      'data' in payload,
  );

const fetchJson = async <T>(apiBaseUrl: string, pathname: string): Promise<T> => {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);

  try {
    const response = await fetch(`${apiBaseUrl}${pathname}`, {
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status} for ${pathname}`);
    }

    const payload = (await response.json()) as ApiResponse<T> | T;
    if (isApiResponse(payload)) {
      if (payload.status !== 'success') {
        throw new Error(payload.message || `API error for ${pathname}`);
      }

      return payload.data;
    }

    return payload;
  } finally {
    clearTimeout(timeout);
  }
};

const fetchAllProducts = async (apiBaseUrl: string) => {
  const products: ProductDto[] = [];
  let page = 1;

  while (true) {
    const payload = await fetchJson<ProductListPayload>(
      apiBaseUrl,
      `/api/public/catalog/products?page=${page}&perPage=48&sort=created_desc`,
    );
    products.push(...payload.items);
    const totalPages = payload.meta?.totalPages ?? page;
    if (page >= totalPages) {
      break;
    }
    page += 1;
  }

  return products;
};

const collectDynamicEntries = async (apiBaseUrl: string): Promise<SitemapEntry[]> => {
  const [categories, products, services, trainings] = await Promise.all([
    fetchJson<{ items: CategoryDto[] }>(apiBaseUrl, '/api/public/catalog/categories').then(
      (payload) => payload.items,
    ),
    fetchAllProducts(apiBaseUrl),
    fetchJson<{ items: ServiceDto[] }>(apiBaseUrl, '/api/public/services').then(
      (payload) => payload.items,
    ),
    fetchJson<{ items: TrainingDto[] }>(apiBaseUrl, '/api/public/trainings').then(
      (payload) => payload.items,
    ),
  ]);

  return [
    ...categories.map((category) => {
      const lastmod = toLastmod(category.updatedAt);
      return {
        loc: `/catalogue/${category.slug}`,
        ...(lastmod ? { lastmod } : {}),
        changefreq: 'weekly' as const,
        priority: '0.8',
      };
    }),
    ...products.map((product) => {
      const lastmod = toLastmod(product.updatedAt);
      return {
        loc: `/catalogue/produits/${product.slug}`,
        ...(lastmod ? { lastmod } : {}),
        changefreq: 'weekly' as const,
        priority: '0.8',
      };
    }),
    ...services.map((service) => ({
      loc: `/services/${service.id}`,
      changefreq: 'monthly' as const,
      priority: '0.7',
    })),
    ...trainings.map((training) => ({
      loc: `/formations/${training.slug}`,
      changefreq: 'monthly' as const,
      priority: '0.7',
    })),
  ];
};

const renderSitemap = (entries: SitemapEntry[]) => {
  const uniqueEntries = Array.from(
    new Map(entries.map((entry) => [toAbsoluteUrl(entry.loc), entry])).values(),
  );

  const urls = uniqueEntries
    .map((entry) => {
      const lines = ['  <url>', `    <loc>${escapeXml(toAbsoluteUrl(entry.loc))}</loc>`];

      if (entry.lastmod) lines.push(`    <lastmod>${escapeXml(entry.lastmod)}</lastmod>`);
      if (entry.changefreq) lines.push(`    <changefreq>${entry.changefreq}</changefreq>`);
      if (entry.priority) lines.push(`    <priority>${entry.priority}</priority>`);

      lines.push('  </url>');
      return lines.join('\n');
    })
    .join('\n');

  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`;
};

const writeSitemap = async (xml: string) => {
  const distPath = path.join(process.cwd(), 'dist', 'sitemap.xml');
  await fs.mkdir(path.dirname(distPath), { recursive: true });
  await fs.writeFile(distPath, xml, 'utf8');
};

const run = async () => {
  const apiBaseUrl = normalizeBaseUrl(resolveApiBaseUrl());
  let entries = staticEntries;

  if (ENABLE_DYNAMIC_SITEMAP) {
    try {
      const dynamicEntries = await collectDynamicEntries(apiBaseUrl);
      entries = [...staticEntries, ...dynamicEntries];
      console.log(`Sitemap dynamique: ${dynamicEntries.length} URL(s) API ajoutée(s).`);
    } catch (error) {
      const message = `Sitemap dynamique indisponible (${error instanceof Error ? error.message : 'erreur inconnue'}).`;

      if (REQUIRE_DYNAMIC_SITEMAP) {
        throw new Error(message);
      }

      console.warn(`${message} Fallback statique utilisé.`);
    }
  } else {
    console.log(
      'Sitemap dynamique désactivé. Définissez SITEMAP_DYNAMIC=1 pour interroger l’API pendant la génération.',
    );
  }

  await writeSitemap(renderSitemap(entries));
  console.log(`Sitemap généré: ${entries.length} URL(s).`);
};

run().catch((error) => {
  console.error('Génération du sitemap échouée:', error);
  process.exit(1);
});
