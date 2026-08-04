import type { HTMLAttributes, PropsWithChildren, ReactNode } from 'react';
import { Link } from 'react-router';

import { cn } from '@/lib/utils';

type PageStateVariant = 'neutral' | 'error' | 'success';

interface PageStateProps extends HTMLAttributes<HTMLDivElement> {
  variant?: PageStateVariant;
}

interface LoadingStateProps extends HTMLAttributes<HTMLDivElement> {
  children?: ReactNode;
}

interface StableContentProps extends HTMLAttributes<HTMLDivElement> {
  children?: ReactNode;
  hasContent: boolean;
  loading: boolean;
  loadingLabel?: ReactNode;
}

const variantClass: Record<PageStateVariant, string> = {
  neutral: 'border-dashed border-brand-100 bg-brand-50 text-stone-600',
  error: 'border-red-200 bg-red-50 text-red-700',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
};

export const PageState = ({
  children,
  className,
  variant = 'neutral',
  ...props
}: PageStateProps) => (
  <div
    className={cn('rounded-2xl border px-4 py-8 text-center', variantClass[variant], className)}
    {...props}
  >
    {children}
  </div>
);

export const LoadingState = ({ children = 'Chargement...', ...props }: LoadingStateProps) => (
  <PageState role="status" aria-live="polite" {...props}>
    <span className="inline-flex h-3 w-3 rounded-full bg-current opacity-70" aria-hidden="true" />
    <span className="sr-only">{children}</span>
  </PageState>
);

export const StableContent = ({
  children,
  className,
  hasContent,
  loading,
  loadingLabel = 'Chargement...',
  ...props
}: StableContentProps) => {
  if (loading && !hasContent) {
    return <LoadingState>{loadingLabel}</LoadingState>;
  }

  return (
    <div
      aria-busy={loading || undefined}
      className={cn(loading && 'opacity-80 transition-opacity', className)}
      {...props}
    >
      {children}
    </div>
  );
};

export const EmptyState = ({ children }: PropsWithChildren) => <PageState>{children}</PageState>;

export const ErrorState = ({ children }: PropsWithChildren) => (
  <PageState variant="error" role="alert">
    {children}
  </PageState>
);

export const FeedbackMessage = ({
  children,
  className,
  role,
  variant = 'error',
  ...props
}: PageStateProps) => (
  <div
    className={cn(
      'register-form__alert',
      variant === 'success' && 'register-form__alert--success',
      variant === 'error' && 'register-form__alert--error',
      className,
    )}
    role={role ?? (variant === 'error' ? 'alert' : 'status')}
    {...props}
  >
    {children}
  </div>
);

export const PrimaryLink = ({ className, ...props }: React.ComponentProps<typeof Link>) => (
  <Link
    className={cn(
      'inline-flex items-center rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700',
      className,
    )}
    {...props}
  />
);
