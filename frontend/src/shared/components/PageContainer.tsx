import type { PropsWithChildren, ReactNode } from 'react';

interface PageContainerProps extends PropsWithChildren {
  title: string;
  headerActions?: ReactNode;
}

export const PageContainer = ({
  title,
  headerActions,
  children,
}: PageContainerProps) => (
  <div className="app-background">
    <div className="card">
      <header className="card__header">
        <h1 className="card__title">
          {title}
        </h1>
        {headerActions}
      </header>
      <main className="card__content">{children}</main>
    </div>
  </div>
);

