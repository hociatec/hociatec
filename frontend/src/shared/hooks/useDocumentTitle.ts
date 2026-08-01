import { useEffect } from 'react';

import { PROJECT_TITLE } from '../config/appConfig';

export const formatDocumentTitle = (pageTitle?: string) =>
  pageTitle ? `${pageTitle} | ${PROJECT_TITLE}` : PROJECT_TITLE;

export const useDocumentTitle = (pageTitle?: string) => {
  useEffect(() => {
    document.title = formatDocumentTitle(pageTitle);
  }, [pageTitle]);
};
