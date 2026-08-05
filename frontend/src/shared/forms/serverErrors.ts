import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';

import { normalizeHttpError } from '@/shared/lib/httpClient';

export type ServerFieldErrors<TFieldValues extends FieldValues> = Partial<
  Record<Path<TFieldValues>, string>
>;

export const getServerFieldErrors = <TFieldValues extends FieldValues>(
  error: unknown,
): ServerFieldErrors<TFieldValues> => {
  const fields = normalizeHttpError(error).fields;
  if (!fields) return {};

  return Object.fromEntries(
    Object.entries(fields).map(([field, messages]) => [field, messages[0] ?? 'Champ invalide.']),
  ) as ServerFieldErrors<TFieldValues>;
};

export const applyServerFieldErrors = <TFieldValues extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<TFieldValues>,
) => {
  const fieldErrors = getServerFieldErrors<TFieldValues>(error);

  Object.entries(fieldErrors).forEach(([field, message]) => {
    if (typeof message !== 'string' || message === '') return;
    setError(field as Path<TFieldValues>, {
      message,
      type: 'server',
    });
  });

  return fieldErrors;
};
