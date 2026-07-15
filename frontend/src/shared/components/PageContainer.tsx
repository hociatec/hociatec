import { useId, type PropsWithChildren, type ReactNode } from 'react';

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
  const titleId = useId();

  return (
    <div className="app-background">
      <section className="card" role="main" aria-labelledby={titleId} tabIndex={-1}>
        <header className="card__header">
          <h1 id={titleId} className="card__title">
            {title}
          </h1>
          {headerActions}
        </header>
        <section className="card__content">{children}</section>
      </section>
    </div>
  );
};
