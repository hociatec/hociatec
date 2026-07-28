import { useId, type PropsWithChildren, type ReactNode } from 'react';

import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { cn } from '@/lib/utils';

interface PageContainerProps extends PropsWithChildren {
  title: string;
  headerActions?: ReactNode;
  size?: 'narrow' | 'medium' | 'wide' | 'admin';
}

export const PageContainer = ({
  title,
  headerActions,
  children,
  size = 'narrow',
}: PageContainerProps) => {
  useDocumentTitle(title);
  const titleId = useId();

  return (
    <div className={cn('app-background', `app-background--${size}`)}>
      <section
        className={cn('card', `card--${size}`)}
        role="main"
        aria-labelledby={titleId}
        tabIndex={-1}
      >
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
