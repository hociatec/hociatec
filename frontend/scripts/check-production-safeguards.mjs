/* global console, process, URL */

import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const projectRoot = new URL('..', import.meta.url).pathname;
const sourceRoot = new URL('../src', import.meta.url).pathname;
const allowedLocationAssignFile = 'shared/lib/redirects.ts';
const allowedDownloadFile = 'shared/lib/downloadFile.ts';
const allowedRandomFile = 'shared/lib/random.ts';
const allowedExternalUrlFile = 'shared/lib/externalUrls.ts';
const allowedStorageFiles = new Set([
  'shared/lib/http/storage.ts',
  'shared/lib/authSessionEvents.ts',
]);
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

const hasRelToken = (tag, token) => {
  const relMatch = tag.match(/\brel=(?:"([^"]*)"|'([^']*)')/);
  const relValue = relMatch?.[1] ?? relMatch?.[2] ?? '';

  return relValue.split(/\s+/).includes(token);
};

const collectBlankTargetViolations = (content, location) => {
  const violations = [];
  const tagPattern = /<a\b[^>]*\btarget=(?:"_blank"|{'_blank'}|'_blank')[^>]*>/g;
  let match;

  while ((match = tagPattern.exec(content))) {
    const tag = match[0];
    if (!hasRelToken(tag, 'noopener') || !hasRelToken(tag, 'noreferrer')) {
      violations.push(`${location}: lien target=_blank sans rel noopener noreferrer`);
    }
  }

  return violations;
};

walk(sourceRoot);

const violations = [];

const requiredSecurityHeaderFiles = [
  join(projectRoot, 'public/_headers'),
  join(projectRoot, '../deploy/nginx/frontend-security-headers.conf'),
];

for (const headerFile of requiredSecurityHeaderFiles) {
  if (!existsSync(headerFile)) {
    violations.push(`${relative(projectRoot, headerFile)}: fichier d'en-tetes de securite introuvable`);
    continue;
  }

  const content = readFileSync(headerFile, 'utf8');
  if (!/Content-Security-Policy\b/.test(content)) {
    violations.push(`${relative(projectRoot, headerFile)}: CSP manquante`);
  }
}

for (const file of files) {
  const content = readFileSync(file, 'utf8');
  const location = relative(sourceRoot, file);

  violations.push(...collectBlankTargetViolations(content, location));

  if (/\bdangerouslySetInnerHTML\b/.test(content)) {
    violations.push(`${location}: injection HTML directe interdite sans composant de sanitization dedie`);
  }

  if (/\baxios\.create\s*\(/.test(content) && location !== 'shared/lib/httpClient.ts') {
    violations.push(`${location}: client HTTP parallele interdit, utiliser shared/lib/httpClient`);
  }

  if (/\bMath\.random\s*\(/.test(content) && location !== allowedRandomFile) {
    violations.push(`${location}: Math.random interdit, utiliser shared/lib/random`);
  }

  if (/\bnew\s+MutationObserver\s*\(/.test(content)) {
    violations.push(`${location}: MutationObserver global interdit pour l'accessibilite, utiliser des composants declaratifs`);
  }

  if (
    /\b(?:window\.)?(?:localStorage|sessionStorage)\.(?:getItem|setItem|removeItem)\s*\(/.test(content) &&
    !allowedStorageFiles.has(location)
  ) {
    violations.push(`${location}: acces storage direct interdit, utiliser shared/lib/http/storage`);
  }

  if (/\bdocument\.createElement\s*\(\s*['"]a['"]\s*\)/.test(content) && location !== allowedDownloadFile) {
    violations.push(`${location}: telechargement ou object URL direct interdit, centraliser dans shared/lib/downloadFile`);
  }

  if (/\bwindow\.open\s*\(/.test(content) && location !== allowedExternalUrlFile) {
    violations.push(`${location}: ouverture externe directe interdite, utiliser shared/lib/externalUrls`);
  }

  if (/\blocation\.assign\s*\(/.test(content) && location !== allowedLocationAssignFile) {
    violations.push(`${location}: redirection directe interdite, utiliser redirectToTrustedUrl`);
  }

  const axiosCreateConfig = content.match(/axios\.create\s*\(\s*\{([\s\S]*?)\n\}\s*\)/);
  if (axiosCreateConfig && /\bheaders\s*:/.test(axiosCreateConfig[1])) {
    violations.push(`${location}: ne pas definir d'en-tetes globaux sur axios.create, notamment Content-Type`);
  }

  if (/localStorage\.setItem\s*\(\s*['"][^'"]*(auth|token|password|secret)/i.test(content)) {
    violations.push(`${location}: stockage local de donnee sensible detecte`);
  }
}

if (violations.length > 0) {
  console.error('Production safeguard violations:');
  for (const violation of violations) {
    console.error(`- ${violation}`);
  }
  process.exit(1);
}

console.log('Production safeguards validated.');
