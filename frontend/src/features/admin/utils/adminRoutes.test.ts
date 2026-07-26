import fs from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';

const projectRoot = process.cwd();
const sourceRoots = ['src/app', 'src/features/admin'].map((dir) => path.join(projectRoot, dir));

const adminRoutePatterns = [
  /^\/admin$/,
  /^\/admin\/operations$/,
  /^\/admin\/trainings$/,
  /^\/admin\/trainings\/new$/,
  /^\/admin\/trainings\/[^/]+\/edit$/,
  /^\/admin\/trainings\/sessions$/,
  /^\/admin\/trainings\/sessions\/new$/,
  /^\/admin\/trainings\/sessions\/[^/]+\/edit$/,
  /^\/admin\/trainings\/enrollments$/,
  /^\/admin\/trainings\/categories$/,
  /^\/admin\/appointments\/prestations$/,
  /^\/admin\/appointments\/prestations\/new$/,
  /^\/admin\/appointments\/prestations\/[^/]+\/edit$/,
  /^\/admin\/appointments\/schedule$/,
  /^\/admin\/catalog\/categories$/,
  /^\/admin\/catalog\/categories\/new$/,
  /^\/admin\/catalog\/categories\/[^/]+\/edit$/,
  /^\/admin\/catalog\/brands$/,
  /^\/admin\/catalog\/brands\/new$/,
  /^\/admin\/catalog\/brands\/[^/]+\/edit$/,
  /^\/admin\/catalog\/products$/,
  /^\/admin\/catalog\/products\/new$/,
  /^\/admin\/catalog\/products\/[^/]+\/edit$/,
  /^\/admin\/quotes$/,
  /^\/admin\/quotes\/[^/]+$/,
  /^\/admin\/quotes\/[^/]+\/edit$/,
  /^\/admin\/services$/,
  /^\/admin\/services\/new$/,
  /^\/admin\/services\/[^/]+\/edit$/,
  /^\/admin\/orders$/,
  /^\/admin\/orders\/[^/]+$/,
  /^\/admin\/payments$/,
  /^\/admin\/payments\/[^/]+$/,
  /^\/admin\/customers$/,
  /^\/admin\/customers\/[^/]+$/,
  /^\/admin\/customers\/[^/]+\/vouchers\/new$/,
  /^\/admin\/marketing$/,
  /^\/admin\/marketing\/new$/,
  /^\/admin\/marketing\/templates$/,
  /^\/admin\/marketing\/templates\/new$/,
  /^\/admin\/marketing\/templates\/[^/]+$/,
  /^\/admin\/marketing\/templates\/[^/]+\/edit$/,
  /^\/admin\/transactional-emails$/,
  /^\/admin\/transactional-emails\/new$/,
  /^\/admin\/transactional-emails\/[^/]+$/,
  /^\/admin\/transactional-emails\/[^/]+\/edit$/,
  /^\/admin\/promotions$/,
  /^\/admin\/promotions\/new$/,
  /^\/admin\/promotions\/[^/]+\/edit$/,
  /^\/admin\/vouchers$/,
  /^\/admin\/vouchers\/new$/,
  /^\/admin\/vouchers\/[^/]+\/edit$/,
  /^\/admin\/audits$/,
  /^\/admin\/audits\/[^/]+$/,
];

const listSourceFiles = (dir: string): string[] => {
  const entries = fs.readdirSync(dir, { withFileTypes: true });

  return entries.flatMap((entry) => {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      return listSourceFiles(fullPath);
    }

    return /\.(tsx?|jsx?)$/.test(entry.name) ? [fullPath] : [];
  });
};

const extractStaticAdminTargets = (content: string) => {
  const targets = new Set<string>();
  const patterns = [
    /\b(?:to|href)=["'](\/admin[^"']*)["']/g,
    /<Navigate[^>]+\bto=["'](\/admin[^"']*)["']/g,
  ];

  for (const pattern of patterns) {
    for (const match of content.matchAll(pattern)) {
      targets.add(match[1].split(/[?#]/)[0]);
    }
  }

  return [...targets];
};

describe('admin route targets', () => {
  it('keeps static admin links aligned with declared routes', () => {
    const deadLinks = sourceRoots
      .flatMap(listSourceFiles)
      .flatMap((file) =>
        extractStaticAdminTargets(fs.readFileSync(file, 'utf8')).map((target) => ({
          file: path.relative(projectRoot, file),
          target,
        })),
      )
      .filter(({ target }) => !adminRoutePatterns.some((pattern) => pattern.test(target)));

    expect(deadLinks).toEqual([]);
  });
});
