import type { InputHTMLAttributes, ReactNode, TextareaHTMLAttributes } from 'react';
import type { FieldError } from 'react-hook-form';

type SharedFieldProps = {
  error?: FieldError | string | undefined;
  hint?: ReactNode;
  id: string;
  label: ReactNode;
};

const errorMessage = (error?: FieldError | string) =>
  typeof error === 'string' ? error : error?.message;

export const fieldA11yProps = (id: string, error?: FieldError | string, hint?: ReactNode) => {
  const message = errorMessage(error);
  const describedBy = [
    hint ? `${id}-hint` : null,
    message ? `${id}-error` : null,
  ].filter(Boolean).join(' ');

  return {
    'aria-describedby': describedBy || undefined,
    'aria-invalid': message ? true : undefined,
  };
};

export const FormField = ({
  children,
  error,
  hint,
  id,
  label,
}: SharedFieldProps & { children: ReactNode }) => {
  const message = errorMessage(error);

  return (
    <label className="register-form__field" htmlFor={id}>
      <span>{label}</span>
      {children}
      {hint ? (
        <small id={`${id}-hint`} className="text-sm text-stone-500">
          {hint}
        </small>
      ) : null}
      {message ? (
        <small id={`${id}-error`} className="text-sm font-semibold text-red-700">
          {message}
        </small>
      ) : null}
    </label>
  );
};

export const TextInputField = ({
  error,
  hint,
  id,
  label,
  ...props
}: SharedFieldProps & InputHTMLAttributes<HTMLInputElement>) => (
  <FormField error={error} hint={hint} id={id} label={label}>
    <input id={id} {...fieldA11yProps(id, error, hint)} {...props} />
  </FormField>
);

export const TextareaField = ({
  error,
  hint,
  id,
  label,
  ...props
}: SharedFieldProps & TextareaHTMLAttributes<HTMLTextAreaElement>) => (
  <FormField error={error} hint={hint} id={id} label={label}>
    <textarea id={id} {...fieldA11yProps(id, error, hint)} {...props} />
  </FormField>
);
