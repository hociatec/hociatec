/* global console, process, URL */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import { gzipSync } from 'node:zlib';

const distRoot = new URL('../dist', import.meta.url).pathname;
const assetsRoot = join(distRoot, 'assets');
const budgets = {
  maxCssGzipBytes: 120 * 1024,
  maxJsChunkGzipBytes: 260 * 1024,
  maxJsGzipBytes: 720 * 1024,
};

const files = [];

const walk = (directory) => {
  for (const entry of readdirSync(directory)) {
    const path = join(directory, entry);
    const stats = statSync(path);

    if (stats.isDirectory()) {
      walk(path);
      continue;
    }

    if (/\.(css|js)$/.test(path)) {
      files.push(path);
    }
  }
};

const formatKb = (bytes) => `${(bytes / 1024).toFixed(1)} KiB`;

walk(assetsRoot);

const assets = files.map((file) => {
  const content = readFileSync(file);

  return {
    file: relative(distRoot, file),
    gzipBytes: gzipSync(content).byteLength,
    kind: file.endsWith('.css') ? 'css' : 'js',
    rawBytes: content.byteLength,
  };
});

const jsAssets = assets.filter((asset) => asset.kind === 'js');
const cssAssets = assets.filter((asset) => asset.kind === 'css');
const totalJsGzip = jsAssets.reduce((total, asset) => total + asset.gzipBytes, 0);
const totalCssGzip = cssAssets.reduce((total, asset) => total + asset.gzipBytes, 0);
const violations = [];

for (const asset of jsAssets) {
  if (asset.gzipBytes > budgets.maxJsChunkGzipBytes) {
    violations.push(
      `${asset.file}: chunk JS gzip ${formatKb(asset.gzipBytes)} > ${formatKb(budgets.maxJsChunkGzipBytes)}`,
    );
  }
}

if (totalJsGzip > budgets.maxJsGzipBytes) {
  violations.push(
    `Total JS gzip ${formatKb(totalJsGzip)} > ${formatKb(budgets.maxJsGzipBytes)}`,
  );
}

if (totalCssGzip > budgets.maxCssGzipBytes) {
  violations.push(
    `Total CSS gzip ${formatKb(totalCssGzip)} > ${formatKb(budgets.maxCssGzipBytes)}`,
  );
}

const largestAssets = assets
  .toSorted((left, right) => right.gzipBytes - left.gzipBytes)
  .slice(0, 8)
  .map((asset) => `- ${asset.file}: ${formatKb(asset.gzipBytes)} gzip (${formatKb(asset.rawBytes)} raw)`)
  .join('\n');

console.log(`Bundle budget: JS ${formatKb(totalJsGzip)} gzip, CSS ${formatKb(totalCssGzip)} gzip.`);
console.log(`Largest assets:\n${largestAssets}`);

if (violations.length > 0) {
  console.error('Bundle budget violations:');
  for (const violation of violations) {
    console.error(`- ${violation}`);
  }
  process.exit(1);
}

console.log('Bundle budget validated.');
