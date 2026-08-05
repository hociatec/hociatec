import { forwardRef, type PropsWithChildren, type SelectHTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

const controlClassName =
  'w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100';

export const PublicTextInput = forwardRef<
  HTMLInputElement,
  React.InputHTMLAttributes<HTMLInputElement>
>(({ className, ...props }, ref) => (
  <input ref={ref} className={cn(controlClassName, className)} {...props} />
));

PublicTextInput.displayName = 'PublicTextInput';

export const PublicTextarea = forwardRef<
  HTMLTextAreaElement,
  React.TextareaHTMLAttributes<HTMLTextAreaElement>
>(({ className, ...props }, ref) => (
  <textarea ref={ref} className={cn(controlClassName, className)} {...props} />
));

PublicTextarea.displayName = 'PublicTextarea';

type PublicSelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
  options: Array<{ value: string; label: string }>;
};

export const PublicSelect = forwardRef<HTMLSelectElement, PublicSelectProps>(
  ({ className, options, ...props }, ref) => (
    <select ref={ref} className={cn(controlClassName, className)} {...props}>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  ),
);

PublicSelect.displayName = 'PublicSelect';

export const PublicSubmitButton = ({
  children,
  className,
  disabled,
  type = 'submit',
}: PropsWithChildren<{
  className?: string;
  disabled?: boolean;
  type?: 'button' | 'submit';
}>) => (
  <button
    type={type}
    disabled={disabled}
    className={cn(
      'inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60',
      className,
    )}
  >
    {children}
  </button>
);
