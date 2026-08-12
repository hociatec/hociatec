import { useEffect } from 'react';

import { PROJECT_TITLE } from '../config/appConfig';

const TITLE_SEPARATOR = '—';

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

export const normalizeDocumentTitle = (pageTitle?: string) => {
  const value = pageTitle?.trim() ?? '';
  if (!value) return '';

  const projectTitlePattern = escapeRegExp(PROJECT_TITLE);
  const leadingProjectTitlePattern = new RegExp(
    `^${projectTitlePattern}(?:\\s*[|\\-–—:]\\s*)?`,
    'iu',
  );
  const trailingProjectTitlePattern = new RegExp(
    `(?:\\s*[|\\-–—:]\\s*)?${projectTitlePattern}$`,
    'iu',
  );

  let normalized = value;
  let previous = '';

  while (normalized && normalized !== previous) {
    previous = normalized;
    normalized = normalized
      .replace(leadingProjectTitlePattern, '')
      .replace(trailingProjectTitlePattern, '')
      .trim();
  }

  return normalized.replace(/^[|\-–—:\s]+|[|\-–—:\s]+$/gu, '').trim();
};

export const formatDocumentTitle = (pageTitle?: string) => {
  const normalizedPageTitle = normalizeDocumentTitle(pageTitle);

  return normalizedPageTitle
    ? `${normalizedPageTitle} ${TITLE_SEPARATOR} ${PROJECT_TITLE}`
    : PROJECT_TITLE;
};

export const useDocumentTitle = (pageTitle?: string) => {
  useEffect(() => {
    document.title = formatDocumentTitle(pageTitle);
  }, [pageTitle]);
};
