import type { Dispatch, SetStateAction } from 'react';

import type { VoucherFormState } from '@/features/admin/customers/hooks/useAdminCustomerVouchers';

type AdminCustomerVoucherConfigurationProps = {
  form: VoucherFormState;
  setForm: Dispatch<SetStateAction<VoucherFormState>>;
  saving: boolean;
  handleSubmit: () => void;
};

export const AdminCustomerVoucherConfiguration = ({
  form,
  setForm,
  saving,
  handleSubmit,
}: AdminCustomerVoucherConfigurationProps) => (
  <div className="grid gap-6 px-6 py-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
    <div className="space-y-4 rounded-xl border border-brand-100 p-5">
      <div>
        <div className="text-sm font-semibold text-brand-900">Configuration du bon</div>
        <p className="mt-1 text-sm text-stone-500">
          Définis le code, la valeur, la période de validité et l’envoi au client.
        </p>
      </div>
      <div className="grid gap-4 md:grid-cols-2">
        <label className="register-form__field">
          <span className="register-form__label">Nom</span>
          <input
            className="register-form__input"
            value={form.name}
            onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
          />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Code</span>
          <input
            className="register-form__input"
            value={form.code}
            onChange={(event) => setForm((prev) => ({ ...prev, code: event.target.value.toUpperCase() }))}
            placeholder="Laisse vide pour génération auto"
          />
        </label>
      </div>
      <div className="grid gap-4 md:grid-cols-2">
        <label className="register-form__field">
          <span className="register-form__label">Type</span>
          <select
            className="register-form__input"
            value={form.discountType}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                discountType: event.target.value as 'percent' | 'fixed_cents',
              }))
            }
          >
            <option value="fixed_cents">Montant fixe en euros</option>
            <option value="percent">Pourcentage</option>
          </select>
        </label>
        <label className="register-form__field">
          <span className="register-form__label">
            Valeur {form.discountType === 'percent' ? '(%)' : '(EUR)'}
          </span>
          <input
            className="register-form__input"
            type="number"
            min={1}
            step={form.discountType === 'percent' ? 1 : 0.01}
            value={form.discountValue}
            onChange={(event) => setForm((prev) => ({ ...prev, discountValue: event.target.value }))}
          />
        </label>
      </div>
      <label className="register-form__field">
        <span className="register-form__label">Description</span>
        <input
          className="register-form__input"
          value={form.description}
          onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
          placeholder="Ex: Offre fidélité valable sur votre prochaine commande"
        />
      </label>
      <div className="grid gap-4 md:grid-cols-2">
        <label className="register-form__field">
          <span className="register-form__label">Début</span>
          <input
            className="register-form__input"
            type="datetime-local"
            value={form.startsAt}
            onChange={(event) => setForm((prev) => ({ ...prev, startsAt: event.target.value }))}
          />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Fin</span>
          <input
            className="register-form__input"
            type="datetime-local"
            value={form.endsAt}
            onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))}
          />
        </label>
      </div>
      <div className="flex flex-wrap items-center gap-6 text-sm text-stone-700">
        <label className="booking__checkbox">
          <input
            type="checkbox"
            checked={form.isActive}
            onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
          />
          Bon actif
        </label>
        <label className="booking__checkbox">
          <input
            type="checkbox"
            checked={form.sendEmail}
            onChange={(event) => setForm((prev) => ({ ...prev, sendEmail: event.target.checked }))}
          />
          Envoyer l’email au client
        </label>
      </div>
      <div className="flex flex-wrap gap-3 pt-2">
        <button
          type="button"
          className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:opacity-60"
          onClick={handleSubmit}
          disabled={saving}
        >
          {saving ? 'Création...' : 'Créer le bon'}
        </button>
      </div>
    </div>

    <aside className="rounded-xl border border-amber-200 bg-amber-50 p-5">
      <div className="text-sm font-semibold text-amber-900">Bonnes pratiques</div>
      <ul className="mt-3 space-y-3 text-sm text-amber-900/80">
        <li>Donne un nom explicite pour retrouver facilement l’offre depuis l’admin.</li>
        <li>Laisse le code vide si tu veux une génération automatique.</li>
        <li>Active l’envoi d’e-mail pour notifier le client immédiatement.</li>
        <li>Renseigne une date de fin pour les offres temporaires ou exceptionnelles.</li>
      </ul>
    </aside>
  </div>
);
