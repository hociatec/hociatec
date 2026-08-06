import { useId, type PropsWithChildren, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface PublicPageShellProps extends PropsWithChildren {
  actions?: ReactNode;
  description?: ReactNode;
  eyebrow?: string;
  size?: 'medium' | 'wide';
  title: string;
}

interface PublicPageSectionProps extends PropsWithChildren {
  className?: string;
}

export const PublicPageShell = ({
  actions,
  children,
  description,
  size = 'wide',
  title,
}: PublicPageShellProps) => {
  const titleId = useId();
  const descriptionId = useId();

  return (
    <main
      className={cn(
        'mx-auto flex w-full flex-col gap-8 px-4 py-10 sm:px-6 lg:px-8',
        size === 'medium' ? 'max-w-3xl' : 'max-w-6xl',
      )}
      aria-labelledby={titleId}
      aria-describedby={description ? descriptionId : undefined}
      data-page-focus-target
      tabIndex={-1}
    >
      <header className="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm sm:p-8">
        <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <h1 id={titleId} className="text-3xl font-semibold tracking-tight text-brand-900 sm:text-4xl">
              {title}
            </h1>
            {description ? (
              <p id={descriptionId} className="mt-3 max-w-2xl text-sm leading-6 text-stone-600 sm:text-base">
                {description}
              </p>
            ) : null}
          </div>
          {actions ? <div className="flex flex-wrap gap-3">{actions}</div> : null}
        </div>
      </header>
      {children}
    </main>
  );
};

export const PublicPageSection = ({ children, className }: PublicPageSectionProps) => (
  <section className={cn('rounded-2xl border border-brand-100 bg-white p-6 shadow-sm', className)}>
    {children}
  </section>
);
