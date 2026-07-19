import fs from 'node:fs/promises';
import path from 'node:path';
import { renderToString } from 'react-dom/server';
import { StaticRouter } from 'react-router';

import { AppProviders } from '../src/app/App';
import { SITE_URL } from '../src/shared/config/seoConfig';

const routesToPrerender = [
  '/',
  '/contact',
  '/services',
  '/appointments/book',
  '/devis/nouveau',
  '/catalogue/vente',
  '/catalogue/location',
  '/catalogue/recherche',
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

const replaceHeadMeta = (html: string, routeUrl: string) => {
  return html
    .replace(/<link rel="canonical" href="[^"]*"/, `<link rel="canonical" href="${routeUrl}"`)
    .replace(/<meta property="og:url" content="[^"]*"/, `<meta property="og:url" content="${routeUrl}"`);
};

const renderRoute = async (template: string, route: string) => {
  const appHtml = renderToString(
    <StaticRouter location={route}>
      <AppProviders />
    </StaticRouter>,
  );

  const routeUrl = route === '/' ? SITE_URL : `${SITE_URL}${route}`;
  const htmlWithMeta = replaceHeadMeta(template, routeUrl);
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
