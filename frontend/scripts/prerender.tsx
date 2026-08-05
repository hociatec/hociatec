import fs from 'node:fs/promises';
import path from 'node:path';
import { renderToString } from 'react-dom/server';
import { StaticRouter } from 'react-router';

import { AppProviders } from '../src/app/App';
import {
  DEFAULT_SEO,
  resolveStaticRouteSeo,
  SITE_URL,
  toAbsoluteSiteUrl,
} from '../src/shared/config/seoConfig';

const routesToPrerender = [
  '/',
  '/contact',
  '/services',
  '/formations',
  '/catalogue/vente',
  '/catalogue/location',
  '/legal/cgu',
  '/legal/cgv',
  '/legal/confidentialite',
  '/legal/mentions-legales',
];

const projectRoot = process.cwd();
const distDir = path.join(projectRoot, 'dist');

const ensureTemplate = async () => {
  try {
    const template = await fs.readFile(path.join(distDir, 'index.html'), 'utf8');
    return template;
  } catch {
    throw new Error(
      'Impossible de charger dist/index.html. Lancez "npm run build" avant le prérendu.',
    );
  }
};

const replaceOrInsertHead = (html: string, pattern: RegExp, replacement: string) => {
  if (pattern.test(html)) {
    return html.replace(pattern, replacement);
  }

  return html.replace('</head>', `    ${replacement}\n  </head>`);
};

const replaceHeadMeta = (html: string, route: string, routeUrl: string) => {
  const seo = resolveStaticRouteSeo(route);
  const title = seo?.title ?? DEFAULT_SEO.title;
  const description = seo?.description ?? DEFAULT_SEO.description;
  const robots = seo?.robots ?? DEFAULT_SEO.robots;
  const imageUrl = toAbsoluteSiteUrl(DEFAULT_SEO.ogImagePath);

  const htmlWithMeta = html
    .replace(/<title>.*?<\/title>/, `<title>${title}</title>`)
    .replace(/<meta name="description" content="[^"]*"/, `<meta name="description" content="${description}"`)
    .replace(/<meta name="robots" content="[^"]*"/, `<meta name="robots" content="${robots}"`)
    .replace(/<meta property="og:title" content="[^"]*"/, `<meta property="og:title" content="${title}"`)
    .replace(/<meta property="og:description" content="[^"]*"/, `<meta property="og:description" content="${description}"`)
    .replace(/<meta property="og:url" content="[^"]*"/, `<meta property="og:url" content="${routeUrl}"`)
    .replace(/<meta property="og:image" content="[^"]*"/, `<meta property="og:image" content="${imageUrl}"`)
    .replace(/<meta name="twitter:title" content="[^"]*"/, `<meta name="twitter:title" content="${title}"`)
    .replace(/<meta name="twitter:description" content="[^"]*"/, `<meta name="twitter:description" content="${description}"`)
    .replace(/<meta name="twitter:image" content="[^"]*"/, `<meta name="twitter:image" content="${imageUrl}"`)
    .replace(/<meta name="twitter:card" content="[^"]*"/, `<meta name="twitter:card" content="${DEFAULT_SEO.twitterCard}"`);

  return replaceOrInsertHead(
    htmlWithMeta,
    /<link rel="canonical" href="[^"]*"/,
    `<link rel="canonical" href="${routeUrl}"`,
  );
};

const renderRoute = async (template: string, route: string) => {
  const appHtml = renderToString(
    <StaticRouter location={route}>
      <AppProviders />
    </StaticRouter>,
  );

  const routeUrl = route === '/' ? SITE_URL : `${SITE_URL}${route}`;
  const htmlWithMeta = replaceHeadMeta(template, route, routeUrl);
  const finalHtml = htmlWithMeta.replace('<div id="root"></div>', `<div id="root">${appHtml}</div>`);

  const outputPath =
    route === '/'
      ? path.join(distDir, 'index.html')
      : path.join(distDir, route.replace(/^\//, ''), 'index.html');

  await fs.mkdir(path.dirname(outputPath), { recursive: true });
  await fs.writeFile(outputPath, finalHtml, 'utf8');
  console.log(`✔ Prérendu: ${route}`);
};

const run = async () => {
  const template = await ensureTemplate();
  for (const route of routesToPrerender) {
    await renderRoute(template, route);
  }
};

run().catch((error) => {
  console.error('Prérendu échoué:', error);
  process.exit(1);
});
