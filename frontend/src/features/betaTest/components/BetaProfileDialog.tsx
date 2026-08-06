import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Trash2 } from 'lucide-react';

import type { BetaProfileChoices } from '@/features/betaTest/api/betaApi';
import { BlockingModal } from '@/shared/components/ui/BlockingModal';
import { BetaProfileCheckboxGroup } from './BetaProfileCheckboxGroup';
import type { EditableProfile } from '../lib/betaProfileForm';

type BetaProfileDialogProps = {
  choices: BetaProfileChoices;
  error: string | null;
  form: EditableProfile;
  mode: 'create' | 'edit';
  saving: boolean;
  deleting?: boolean;
  onClose: () => void;
  onDelete?: (() => void | Promise<void>) | undefined;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  setForm: Dispatch<SetStateAction<EditableProfile | null>>;
};

export const BetaProfileDialog = ({
  choices,
  error,
  form,
  mode,
  saving,
  deleting = false,
  onClose,
  onDelete,
  onSubmit,
  setForm,
}: BetaProfileDialogProps) => {
  const busy = saving || deleting;
  const title = mode === 'create' ? 'Activer le profil bêta' : 'Modifier le profil bêta';

  return (
    <BlockingModal
      labelledBy="beta-profile-dialog-title"
      describedBy="beta-profile-dialog-description"
      panelClassName="mx-auto w-full max-w-3xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl"
    >
      <header className="space-y-2">
        <h2 id="beta-profile-dialog-title" className="text-2xl font-bold text-brand-900">
          {title}
        </h2>
        <p id="beta-profile-dialog-description" className="text-sm leading-6 text-stone-600">
          Complétez votre profil pour recevoir des campagnes adaptées et gérer votre participation.
        </p>
      </header>

      <form onSubmit={onSubmit} className="mt-6 space-y-6" aria-busy={busy}>
        <BetaProfileCheckboxGroup
          name="availability"
          label="Disponibilités"
          options={choices.availability ?? []}
          form={form}
          setForm={setForm}
          required
        />

        <div className="space-y-4">
          <label className="block text-sm font-semibold text-stone-700">
            Motivation *
            <textarea
              autoFocus
              data-autofocus
              className="mt-1 w-full rounded-lg border border-stone-300 p-3 text-sm focus:border-brand-700 focus:outline-none"
              rows={4}
              value={form.motivation}
              onChange={(event) => setForm({ ...form, motivation: event.target.value })}
              required
              disabled={busy}
            />
          </label>

          <BetaProfileCheckboxGroup
            name="testingExperience"
            label="Expérience des tests"
            options={choices.testingExperience ?? []}
            form={form}
            setForm={setForm}
            required
          />
          <BetaProfileCheckboxGroup
            name="bugDescriptionAbility"
            label="Capacité à décrire un bug"
            options={choices.bugDescriptionAbility ?? []}
            form={form}
            setForm={setForm}
            required
          />
          <BetaProfileCheckboxGroup
            name="technicalKnowledge"
            label="Connaissances techniques"
            options={choices.technicalKnowledge ?? []}
            form={form}
            setForm={setForm}
            required
          />
        </div>

        <BetaProfileCheckboxGroup
          name="assistiveTools"
          label="Outils utilisés"
          options={choices.assistiveTools ?? []}
          form={form}
          setForm={setForm}
          required
        />
        <BetaProfileCheckboxGroup
          name="devices"
          label="Matériel *"
          options={choices.devices ?? []}
          form={form}
          setForm={setForm}
          required
        />
        <BetaProfileCheckboxGroup
          name="browsers"
          label="Navigateurs *"
          options={choices.browsers ?? []}
          form={form}
          setForm={setForm}
          required
        />
        <BetaProfileCheckboxGroup
          name="testingTypes"
          label="Types de tests souhaités *"
          options={choices.testingTypes ?? []}
          form={form}
          setForm={setForm}
          required
        />

        <label className="flex cursor-pointer select-none items-start gap-2.5 text-sm">
          <input
            name="betaConsent"
            type="checkbox"
            checked={form.betaConsent}
            onChange={(event) => setForm({ ...form, betaConsent: event.target.checked })}
            required
            disabled={busy}
            aria-label="J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin."
            className="mt-0.5 h-4 w-4 rounded border-stone-300 text-brand-700 focus:ring-brand-500"
          />
          <span aria-hidden="true" className="text-stone-600">
            J’accepte de participer au programme bêta et l’utilisation de ces informations à cette fin.
          </span>
        </label>

        {error ? <p className="text-sm font-semibold text-red-600">{error}</p> : null}

        <div className="flex flex-col-reverse gap-3 border-t border-stone-150 pt-4 sm:flex-row sm:items-center sm:justify-between">
          {mode === 'edit' && onDelete ? (
            <button
              type="button"
              disabled={busy}
              onClick={onDelete}
              className="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60"
            >
              <Trash2 size={16} aria-hidden="true" />
              {deleting ? 'Suppression...' : 'Supprimer mon profil bêta'}
            </button>
          ) : (
            <span />
          )}
          <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
              type="button"
              disabled={busy}
              onClick={onClose}
              className="rounded-lg border border-brand-100 px-5 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 disabled:opacity-60"
            >
              Annuler
            </button>
            <button
              type="submit"
              disabled={busy}
              className="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 disabled:opacity-60"
            >
              {saving ? 'Enregistrement...' : 'Enregistrer'}
            </button>
          </div>
        </div>
      </form>
    </BlockingModal>
  );
};
