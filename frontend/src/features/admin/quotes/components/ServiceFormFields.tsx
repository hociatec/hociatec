import type { ChangeEvent, Dispatch, SetStateAction } from 'react';

export type ServiceFormState = {
  title: string;
  description: string;
  unit: string;
  durationValue: string;
  durationUnit: 'hour' | 'day';
  price: string;
  vatRate: string;
};

type ServiceFormFieldsProps = {
  form: ServiceFormState;
  setForm: Dispatch<SetStateAction<ServiceFormState>>;
};

export const ServiceFormFields = ({ form, setForm }: ServiceFormFieldsProps) => {
  const handleChange =
    (field: keyof ServiceFormState) =>
    (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      setForm((prev) => ({ ...prev, [field]: event.target.value }));
    };

  return (
    <>
      <label className="register-form__field">
        <span className="register-form__label">Titre</span>
        <input
          className="register-form__input"
          placeholder="Intitulé du service"
          value={form.title}
          onChange={handleChange('title')}
          required
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Description</span>
        <textarea
          className="register-form__input"
          rows={4}
          placeholder="Détails affichés dans le catalogue et les parcours associés"
          value={form.description}
          onChange={handleChange('description')}
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Mode de facturation</span>
        <input
          className="register-form__input"
          type="text"
          value={form.unit}
          onChange={(event) => setForm((prev) => ({ ...prev, unit: event.target.value }))}
          placeholder="Unité de facturation"
        />
      </label>
      <div className="grid gap-4 md:grid-cols-[1fr_180px]">
        <label className="register-form__field">
          <span className="register-form__label">Durée estimée</span>
          <input
            className="register-form__input"
            type="number"
            min="1"
            step="1"
            inputMode="numeric"
            placeholder="Ex: 2"
            value={form.durationValue}
            onChange={handleChange('durationValue')}
          />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Unité de durée</span>
          <select
            className="register-form__input"
            value={form.durationUnit}
            onChange={(event) =>
              setForm((prev) => ({
                ...prev,
                durationUnit: event.target.value === 'day' ? 'day' : 'hour',
              }))
            }
          >
            <option value="hour">Heure(s)</option>
            <option value="day">Jour(s)</option>
          </select>
        </label>
      </div>
      <label className="register-form__field">
        <span className="register-form__label">Prix HT (EUR)</span>
        <input
          className="register-form__input"
          type="number"
          min="0"
          step="0.01"
          value={form.price}
          onChange={handleChange('price')}
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">TVA (%)</span>
        <input
          className="register-form__input"
          type="number"
          min="0"
          step="0.1"
          value={form.vatRate}
          onChange={handleChange('vatRate')}
        />
      </label>
    </>
  );
};
