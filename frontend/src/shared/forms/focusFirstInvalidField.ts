import type { FieldErrors, FieldValues, Path, UseFormSetFocus } from 'react-hook-form';

export const focusFirstInvalidField = <TFieldValues extends FieldValues>(
  errors: FieldErrors<TFieldValues>,
  setFocus: UseFormSetFocus<TFieldValues>,
) => {
  const [firstField] = Object.keys(errors);
  if (firstField) {
    setFocus(firstField as Path<TFieldValues>);
  }
};
