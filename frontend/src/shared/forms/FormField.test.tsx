// @vitest-environment jsdom
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { fieldA11yProps, TextInputField } from './FormField';

describe('FormField', () => {
  it('links hints and validation messages through aria-describedby', () => {
    render(
      <TextInputField
        id="email"
        label="Email"
        hint="Utilisez votre adresse professionnelle."
        error="Adresse invalide."
      />,
    );

    const input = screen.getByLabelText(/Email/);

    expect(input.getAttribute('aria-invalid')).toBe('true');
    expect(input.getAttribute('aria-describedby')).toBe('email-hint email-error');
    expect(screen.getByText('Utilisez votre adresse professionnelle.')).toBeTruthy();
    expect(screen.getByText('Adresse invalide.')).toBeTruthy();
  });

  it('omits error attributes when the field is valid', () => {
    expect(fieldA11yProps('name')).toEqual({
      'aria-describedby': undefined,
      'aria-invalid': undefined,
    });
  });
});
