import { describe, expect, it, vi } from 'vitest';

import { applyServerFieldErrors, getServerFieldErrors } from './serverErrors';
import { focusFirstInvalidField } from './focusFirstInvalidField';
import { ApiResponseError } from '@/shared/lib/httpClient';

type ContactFields = {
  email: string;
  message: string;
};

describe('form server errors', () => {
  it('maps API validation fields to form field errors', () => {
    const error = new ApiResponseError(
      'Validation failed.',
      [],
      undefined,
      'VALIDATION_ERROR',
      {
        email: ['Adresse déjà utilisée.'],
        message: ['Message trop court.'],
      },
    );

    expect(getServerFieldErrors<ContactFields>(error)).toEqual({
      email: 'Adresse déjà utilisée.',
      message: 'Message trop court.',
    });
  });

  it('applies API validation fields through react-hook-form setError', () => {
    const setError = vi.fn();
    const error = new ApiResponseError('Validation failed.', [], undefined, undefined, {
      email: ['Adresse invalide.'],
    });

    applyServerFieldErrors<ContactFields>(error, setError);

    expect(setError).toHaveBeenCalledWith('email', {
      message: 'Adresse invalide.',
      type: 'server',
    });
  });
});

describe('focusFirstInvalidField', () => {
  it('focuses the first invalid field', () => {
    const setFocus = vi.fn();

    focusFirstInvalidField<ContactFields>({ message: { message: 'Requis', type: 'required' } }, setFocus);

    expect(setFocus).toHaveBeenCalledWith('message');
  });
});
