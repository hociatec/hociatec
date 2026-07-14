import type { PropsWithChildren, ReactNode } from 'react';

import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

interface PageContainerProps extends PropsWithChildren {
  title: string;
  headerActions?: ReactNode;
}

export const PageContainer = ({
  title,
  headerActions,
  children,
}: PageContainerProps) => {
  useDocumentTitle(title);

  return (
    <div className="app-background">
      <div className="card">
        <header className="card__header">
          <h1 className="card__title">
            {title}
          </h1>
          {headerActions}
        </header>
        <section className="card__content">{children}</section>
      </div>
    </div>
  );
};
