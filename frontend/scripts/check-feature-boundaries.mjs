/* global URL, console, process */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { relative, join } from 'node:path';

const sourceRoot = new URL('../src', import.meta.url).pathname;
const files = [];

const walk = (directory) => {
  for (const entry of readdirSync(directory)) {
    const path = join(directory, entry);
    const stats = statSync(path);

    if (stats.isDirectory()) {
      walk(path);
      continue;
    }

    if (/\.(ts|tsx)$/.test(path)) {
      files.push(path);
    }
  }
};

const resolveSourceLayer = (file) => {
  const relativePath = relative(sourceRoot, file);

  if (relativePath.startsWith('shared/')) return { layer: 'shared' };
  if (relativePath.startsWith('app/')) return { layer: 'app' };
  if (relativePath.startsWith('features/')) {
    return { layer: 'feature', feature: relativePath.split('/')[1] };
  }

  return { layer: 'other' };
};

walk(sourceRoot);

const importPattern = /from\s+['"](@\/features\/([^/'"]+)(?:\/([^'"]+))?)['"]/g;
const violations = [];
const featureEntrypoints = new Set(['publicApi', 'adminApi', 'typesApi', 'uiApi']);

for (const file of files) {
  const source = resolveSourceLayer(file);
  const content = readFileSync(file, 'utf8');
  let match;

  while ((match = importPattern.exec(content))) {
    const [, specifier, targetFeature, targetPath = ''] = match;
    const location = relative(sourceRoot, file);

    if (source.layer === 'shared') {
      violations.push({
        location,
        specifier,
        reason: 'shared ne doit jamais dépendre de features',
      });
      continue;
    }

    if (
      source.layer === 'feature' &&
      source.feature !== targetFeature &&
      !featureEntrypoints.has(targetPath)
    ) {
      violations.push({
        location,
        specifier,
        reason: 'les imports inter-features doivent passer par un point d’entrée public',
      });
    }
  }
}

if (violations.length > 0) {
  console.error('Front-end architecture boundary violations:');
  for (const violation of violations) {
    console.error(`- ${violation.location}: ${violation.specifier} (${violation.reason})`);
  }
  process.exit(1);
}
