/* global console, process, URL */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { basename, dirname, extname, join, relative, resolve } from 'node:path';

const sourceRoot = new URL('../src', import.meta.url).pathname;
const projectRoot = new URL('..', import.meta.url).pathname;
const packageJsonPath = join(projectRoot, 'package.json');
const warningThresholds = {
  cssFileLines: 700,
  fileLines: 550,
  branchKeywords: 70,
  parameters: 9,
};

const hardForbiddenPatterns = [
  {
    pattern: /\bwindow\.confirm\s*\(/,
    message: 'utiliser un dialogue applicatif au lieu de window.confirm',
  },
];

const files = [];
const sourceFiles = [];
const cssImports = new Set();

const walk = (directory) => {
  for (const entry of readdirSync(directory)) {
    const path = join(directory, entry);
    const stats = statSync(path);

    if (stats.isDirectory()) {
      walk(path);
      continue;
    }

    if (/\.(ts|tsx|css)$/.test(path)) {
      files.push(path);
    }
  }
};

const countLines = (content) => content.split(/\r?\n/).length;

const countBranchKeywords = (content) => {
  const matches = content.match(/\b(if|else if|switch|case|catch|for|while)\b|&&|\|\||\?/g);

  return matches?.length ?? 0;
};

const countTopLevelParameters = (parameters) => {
  if (parameters.trim() === '') return 0;

  let depth = 0;
  let count = 1;

  for (const char of parameters) {
    if (char === '<' || char === '(' || char === '[' || char === '{') depth += 1;
    if (char === '>' || char === ')' || char === ']' || char === '}') depth = Math.max(0, depth - 1);
    if (char === ',' && depth === 0) count += 1;
  }

  return count;
};

const collectLongParameterLists = (content) => {
  const warnings = [];
  const signaturePattern =
    /(?:function\s+[A-Za-z0-9_$]+\s*|const\s+[A-Za-z0-9_$]+\s*=\s*(?:async\s*)?\(|(?:async\s*)?\([^)]*\)\s*=>|[A-Za-z0-9_$]+\s*:\s*\([^)]*\)\s*=>)/g;
  let match;

  while ((match = signaturePattern.exec(content))) {
    const start = content.indexOf('(', match.index);
    if (start < 0) continue;

    let depth = 0;
    let end = -1;

    for (let index = start; index < content.length; index += 1) {
      const char = content[index];
      if (char === '(') depth += 1;
      if (char === ')') depth -= 1;
      if (depth === 0) {
        end = index;
        break;
      }
    }

    if (end < 0) continue;

    const parameterCount = countTopLevelParameters(content.slice(start + 1, end));
    if (parameterCount >= warningThresholds.parameters) {
      const line = countLines(content.slice(0, start));
      warnings.push({ line, parameterCount });
    }
  }

  return warnings;
};

const isGeneratedFile = (file) => relative(sourceRoot, file).startsWith('shared/api/generated/');

const isApiLikeFile = (file) => {
  const name = basename(file);

  return /api/i.test(name) && !/^(api|.*Api|publicApi|adminApi|typesApi|uiApi|apiShared|.*ApiShared)\.(ts|tsx)$/.test(name);
};

const collectImportSpecifiers = (content) => {
  const specifiers = [];
  const patterns = [
    /import\s+(?:[^'"]+\s+from\s+)?['"]([^'"]+)['"]/g,
    /export\s+[^'"]+\s+from\s+['"]([^'"]+)['"]/g,
    /import\s*\(\s*['"]([^'"]+)['"]\s*\)/g,
  ];

  for (const pattern of patterns) {
    let match;
    while ((match = pattern.exec(content))) {
      specifiers.push(match[1]);
    }
  }

  return specifiers;
};

const resolveCssImport = (file, specifier) => {
  if (!specifier.endsWith('.css')) return null;
  if (specifier.startsWith('@/')) return resolve(sourceRoot, specifier.slice(2));
  if (specifier.startsWith('.')) return resolve(dirname(file), specifier);

  return null;
};

const getPackageName = (specifier) => {
  if (specifier.startsWith('.') || specifier.startsWith('@/') || specifier.startsWith('#')) return null;
  if (specifier.startsWith('@')) {
    const [scope, name] = specifier.split('/');

    return scope && name ? `${scope}/${name}` : specifier;
  }

  return specifier.split('/')[0];
};

const collectExportedNames = (content) => {
  const names = [];
  const patterns = [
    /export\s+(?:async\s+)?function\s+([A-Za-z0-9_$]+)/g,
    /export\s+(?:const|let|var|class|type|interface|enum)\s+([A-Za-z0-9_$]+)/g,
  ];

  for (const pattern of patterns) {
    let match;
    while ((match = pattern.exec(content))) {
      names.push(match[1]);
    }
  }

  return names;
};

walk(sourceRoot);

const warnings = [];
const errors = [];
const importedPackages = new Set();
const allSourceContent = [];

for (const file of files) {
  const content = readFileSync(file, 'utf8');
  const location = relative(sourceRoot, file);
  const lineCount = countLines(content);
  const extension = extname(file);

  if (extension === '.ts' || extension === '.tsx') {
    sourceFiles.push(file);
    allSourceContent.push({ file, content });
  }

  for (const specifier of collectImportSpecifiers(content)) {
    const packageName = getPackageName(specifier);
    if (packageName) importedPackages.add(packageName);

    const cssImport = resolveCssImport(file, specifier);
    if (cssImport) cssImports.add(cssImport);
  }

  for (const forbidden of hardForbiddenPatterns) {
    if (forbidden.pattern.test(content)) {
      errors.push(`${location}: ${forbidden.message}`);
    }
  }

  if (extension === '.css' && lineCount > warningThresholds.cssFileLines) {
    warnings.push(`${location}: fichier CSS long (${lineCount} lignes)`);
  }

  if ((extension === '.ts' || extension === '.tsx') && lineCount > warningThresholds.fileLines) {
    warnings.push(`${location}: fichier TypeScript long (${lineCount} lignes)`);
  }

  if (
    (extension === '.ts' || extension === '.tsx') &&
    !isGeneratedFile(file) &&
    countBranchKeywords(content) > warningThresholds.branchKeywords
  ) {
    warnings.push(`${location}: densite conditionnelle elevee`);
  }

  if ((extension === '.ts' || extension === '.tsx') && !isGeneratedFile(file) && isApiLikeFile(file)) {
    warnings.push(`${location}: nom de fichier API a clarifier selon docs/maintainability.md`);
  }

  if (!isGeneratedFile(file)) {
    for (const longParameters of collectLongParameterLists(content)) {
      warnings.push(
        `${location}:${longParameters.line}: signature longue (${longParameters.parameterCount} parametres)`,
      );
    }
  }
}

for (const file of files) {
  if (extname(file) === '.css' && !cssImports.has(file)) {
    warnings.push(`${relative(sourceRoot, file)}: fichier CSS non importe detecte`);
  }
}

for (const { file, content } of allSourceContent) {
  if (isGeneratedFile(file) || /(?:publicApi|adminApi|typesApi|uiApi)\.ts$/.test(file)) continue;

  for (const exportedName of collectExportedNames(content)) {
    const usageCount = allSourceContent.reduce((count, current) => {
      const matches = current.content.match(new RegExp(`\\b${exportedName}\\b`, 'g'));

      return count + (matches?.length ?? 0);
    }, 0);

    if (usageCount <= 1) {
      warnings.push(`${relative(sourceRoot, file)}: export possiblement inutilise (${exportedName})`);
    }
  }
}

const packageJson = JSON.parse(readFileSync(packageJsonPath, 'utf8'));
for (const dependency of Object.keys(packageJson.dependencies ?? {})) {
  if (!importedPackages.has(dependency)) {
    warnings.push(`package.json: dependance runtime possiblement inutilisee (${dependency})`);
  }
}

if (warnings.length > 0) {
  console.warn('Maintainability warnings:');
  for (const warning of warnings) {
    console.warn(`- ${warning}`);
  }
}

if (errors.length > 0) {
  console.error('Maintainability violations:');
  for (const error of errors) {
    console.error(`- ${error}`);
  }
  process.exit(1);
}

console.log('Maintainability check completed.');
