// @vitest-environment jsdom
import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import axe from 'axe-core';

import { ErrorState, FeedbackMessage, ForbiddenState, NotFoundState } from './page-state';

const runAxe = async (element: Element) => {
  const result = await axe.run(element);
  return result.violations;
};

describe('page state accessibility', () => {
  it('keeps error states accessible with retry actions', async () => {
    const { container } = render(
      <ErrorState onAction={() => undefined}>
        Le chargement a échoué.
      </ErrorState>,
    );

    expect(await runAxe(container)).toEqual([]);
  });

  it('uses semantic live regions for feedback messages', async () => {
    const { container, getByText } = render(
      <>
        <FeedbackMessage>Erreur de validation.</FeedbackMessage>
        <FeedbackMessage variant="success">Enregistré.</FeedbackMessage>
      </>,
    );

    expect(getByText('Erreur de validation.').getAttribute('role')).toBe('alert');
    expect(getByText('Enregistré.').getAttribute('role')).toBe('status');
    expect(await runAxe(container)).toEqual([]);
  });

  it('exposes forbidden and not-found states with stable semantics', async () => {
    const { container, getByText } = render(
      <>
        <ForbiddenState>Accès refusé.</ForbiddenState>
        <NotFoundState>Ressource introuvable.</NotFoundState>
      </>,
    );

    expect(getByText('Accès refusé.').getAttribute('role')).toBe('alert');
    expect(await runAxe(container)).toEqual([]);
  });
});
