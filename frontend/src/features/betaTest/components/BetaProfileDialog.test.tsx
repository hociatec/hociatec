// @vitest-environment jsdom
import { useState, type FormEvent } from 'react';
import { cleanup, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

import type { BetaProfileChoices } from '../api/betaApi';
import { emptyBetaProfileForm, type EditableProfile } from '../lib/betaProfileForm';
import { BetaProfileDialog } from './BetaProfileDialog';

const choices: BetaProfileChoices = {
  availability: [{ value: 'flexible', label: 'Flexible' }],
  testingExperience: [{ value: 'first', label: 'Première expérience' }],
  bugDescriptionAbility: [{ value: 'clear', label: 'Description claire' }],
  technicalKnowledge: [{ value: 'basic', label: 'Bases techniques' }],
  assistiveTools: [{ value: 'none', label: 'Aucun outil' }],
  devices: [{ value: 'windows', label: 'Windows' }],
  browsers: [{ value: 'chrome', label: 'Chrome' }],
  testingTypes: [{ value: 'bugs', label: 'Signalement de bugs' }],
};

const BetaProfileDialogHarness = () => {
  const [form, setForm] = useState<EditableProfile | null>(() => emptyBetaProfileForm());

  if (!form) return null;

  return (
    <BetaProfileDialog
      choices={choices}
      error={null}
      form={form}
      mode="create"
      saving={false}
      onClose={() => undefined}
      onSubmit={(event: FormEvent<HTMLFormElement>) => event.preventDefault()}
      setForm={setForm}
    />
  );
};

afterEach(() => {
  cleanup();
});

describe('BetaProfileDialog', () => {
  it('moves focus directly into the dialog when it opens', async () => {
    render(<BetaProfileDialogHarness />);

    await waitFor(() => {
      expect(document.activeElement).toBe(screen.getByLabelText('Motivation *'));
    });
  });
});
