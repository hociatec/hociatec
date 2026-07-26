import { useEffect } from 'react';

import { PROJECT_TITLE } from '../config/appConfig';

export const useDocumentTitle = (pageTitle?: string) => {
  useEffect(() => {
    const formattedTitle = pageTitle ? `${pageTitle} | ${PROJECT_TITLE}` : PROJECT_TITLE;

    document.title = formattedTitle;
  }, [pageTitle]);
};
