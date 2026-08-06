import type { HTMLAttributes, ReactNode } from 'react';

import { cn } from '@/lib/utils';

type AlertVariant = 'info' | 'success' | 'error' | 'warning';

interface AlertProps extends HTMLAttributes<HTMLDivElement> {
  children: ReactNode;
  variant?: AlertVariant;
}

const variantClass: Record<AlertVariant, string> = {
  error: 'border-[var(--color-feedback-error-border)] bg-[var(--color-feedback-error-bg)] text-[var(--color-feedback-error-text)]',
  info: 'border-[var(--color-feedback-info-border)] bg-[var(--color-feedback-info-bg)] text-[var(--color-feedback-info-text)]',
  success: 'border-[var(--color-feedback-success-border)] bg-[var(--color-feedback-success-bg)] text-[var(--color-feedback-success-text)]',
  warning: 'border-[var(--color-feedback-warning-border)] bg-[var(--color-feedback-warning-bg)] text-[var(--color-feedback-warning-text)]',
};

export const Alert = ({ children, className, role, variant = 'info', ...props }: AlertProps) => (
  <div
    aria-atomic="true"
    aria-live={variant === 'error' ? 'assertive' : 'polite'}
    className={cn('rounded-xl border px-4 py-3 text-sm', variantClass[variant], className)}
    role={role ?? (variant === 'error' ? 'alert' : 'status')}
    {...props}
  >
    {children}
  </div>
);
