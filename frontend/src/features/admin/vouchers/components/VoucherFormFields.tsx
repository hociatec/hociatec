import type { Dispatch, SetStateAction } from 'react';

export type VoucherFormState = {
  name: string;
  code: string;
  description: string;
  discountType: 'percent' | 'fixed_cents';
  discountValue: string;
  isActive: boolean;
  startsAt: string;
  endsAt: string;
};

type VoucherFormFieldsProps = {
  form: VoucherFormState;
  setForm: Dispatch<SetStateAction<VoucherFormState>>;
};

export const VoucherFormFields = ({ form, setForm }: VoucherFormFieldsProps) => (
  <>
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span className="register-form__label">Code</span>
        <input
          className="register-form__input"
          value={form.code}
          onChange={(event) => setForm((prev) => ({ ...prev, code: event.target.value.toUpperCase() }))}
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Nom</span>
        <input
          className="register-form__input"
          value={form.name}
          onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
        />
      </label>
    </div>
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span className="register-form__label">Type de remise</span>
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
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span className="register-form__label">Description</span>
        <input
          className="register-form__input"
          value={form.description}
          onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Début</span>
        <input
          className="register-form__input"
          type="datetime-local"
          value={form.startsAt}
          onChange={(event) => setForm((prev) => ({ ...prev, startsAt: event.target.value }))}
        />
      </label>
    </div>
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span className="register-form__label">Fin</span>
        <input
          className="register-form__input"
          type="datetime-local"
          value={form.endsAt}
          onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))}
        />
      </label>
      <label className="booking__checkbox md:mt-8">
        <input
          type="checkbox"
          checked={form.isActive}
          onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
        />
        Bon actif
      </label>
    </div>
  </>
);
