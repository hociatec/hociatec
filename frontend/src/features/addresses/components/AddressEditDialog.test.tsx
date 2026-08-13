// @vitest-environment jsdom
import { afterEach, describe, expect, it } from 'vitest';
import { cleanup, render, screen, waitFor } from '@testing-library/react';

import { emptyAddressForm } from '@/features/addresses/types/address';
import { AddressEditDialog } from './AddressEditDialog';

afterEach(() => {
  cleanup();
});

describe('AddressEditDialog', () => {
  it('renders address creation in a blocking dialog', () => {
    render(
      <AddressEditDialog
        mode="create"
        form={emptyAddressForm()}
        saving={false}
        setForm={() => undefined}
        onClose={() => undefined}
        onSubmit={() => undefined}
      />,
    );

    expect(screen.getByRole('dialog', { name: 'Ajouter une adresse' })).toBeTruthy();
    expect(screen.getByLabelText('Nom')).toBeTruthy();
    expect(screen.getByLabelText('Définir comme adresse par défaut')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Ajouter l’adresse' })).toBeTruthy();
  });

  it('moves focus into the address dialog when it opens', async () => {
    render(
      <AddressEditDialog
        mode="create"
        form={emptyAddressForm()}
        saving={false}
        setForm={() => undefined}
        onClose={() => undefined}
        onSubmit={() => undefined}
      />,
    );

    await waitFor(() => {
      expect(document.activeElement).toBe(screen.getByLabelText('Personnel'));
    });
  });

  it('renders address edition without default-address creation control', () => {
    render(
      <AddressEditDialog
        form={emptyAddressForm()}
        saving={false}
        setForm={() => undefined}
        onClose={() => undefined}
        onSubmit={() => undefined}
      />,
    );

    expect(screen.getByRole('dialog', { name: 'Modifier l’adresse' })).toBeTruthy();
    expect(screen.queryByLabelText('Définir comme adresse par défaut')).toBeNull();
    expect(screen.getByRole('button', { name: 'Enregistrer' })).toBeTruthy();
  });
});
