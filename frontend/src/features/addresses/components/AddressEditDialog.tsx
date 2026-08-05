import type { Dispatch, SetStateAction } from 'react';
import type { AddressFormState } from '@/features/addresses/types/address';
import { BlockingModal } from '@/shared/components/ui/BlockingModal';
import { AddressFields } from './AddressFields';

export const AddressEditDialog = ({
  form,
  mode = 'edit',
  saving,
  setForm,
  onClose,
  onSubmit,
}: {
  form: AddressFormState;
  mode?: 'create' | 'edit';
  saving: boolean;
  setForm: Dispatch<SetStateAction<AddressFormState>>;
  onClose: () => void;
  onSubmit: () => void;
}) => {
  const isCreate = mode === 'create';

  return (
    <BlockingModal labelledBy="address-dialog-title" describedBy="address-dialog-description">
      <header className="space-y-2">
        <h2 id="address-dialog-title" className="text-2xl font-semibold text-brand-900">
          {isCreate ? 'Ajouter une adresse' : 'Modifier l’adresse'}
        </h2>
        <p id="address-dialog-description" className="text-sm text-stone-600">
          {isCreate
            ? 'Renseignez une adresse claire pour faciliter la livraison et la facturation.'
            : 'Mettez à jour les informations sans perdre vos commandes existantes.'}
        </p>
      </header>

      <form
        className="mt-6 grid gap-4"
        aria-busy={saving}
        onSubmit={(event) => {
          event.preventDefault();
          onSubmit();
        }}
      >
        <AddressFields form={form} setForm={setForm} />
        {isCreate ? (
          <label className="flex items-center gap-3 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-stone-700">
            <input
              type="checkbox"
              checked={Boolean(form.isDefault)}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  isDefault: event.target.checked,
                }))
              }
            />
            Définir comme adresse par défaut
          </label>
        ) : null}

        <div className="mt-2 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            className="address-button justify-center"
            onClick={onClose}
            disabled={saving}
          >
            Annuler
          </button>
          <button type="submit" className="address-submit" disabled={saving}>
            {saving
              ? isCreate
                ? 'Ajout en cours...'
                : 'Enregistrement...'
              : isCreate
                ? 'Ajouter l’adresse'
                : 'Enregistrer'}
          </button>
        </div>
      </form>
    </BlockingModal>
  );
};
