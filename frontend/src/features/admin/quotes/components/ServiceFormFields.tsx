import type { ChangeEvent, Dispatch, SetStateAction } from 'react';

export type ServiceFormState = {
  title: string;
  description: string;
  unit: string;
  isFeaturedHome: boolean;
  imageUrl: string;
  imageAlt: string;
  imageFile: File | null;
  currentImageUrl: string;
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

  const handleCheckboxChange = (event: ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({ ...prev, isFeaturedHome: event.target.checked }));
  };

  const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
    const nextFile = event.target.files?.[0] ?? null;
    setForm((prev) => ({ ...prev, imageFile: nextFile }));
  };

  const previewUrl =
    form.imageFile !== null
      ? URL.createObjectURL(form.imageFile)
      : form.imageUrl.trim() || form.currentImageUrl.trim();

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
      <label className="booking__checkbox">
        <input
          type="checkbox"
          checked={form.isFeaturedHome}
          onChange={handleCheckboxChange}
        />
        Mettre en avant sur la page d'accueil
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Image d'illustration (fichier)</span>
        <input
          className="register-form__input"
          type="file"
          accept="image/*"
          onChange={handleFileChange}
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Ou URL d'illustration</span>
        <input
          className="register-form__input"
          type="url"
          placeholder="https://..."
          value={form.imageUrl}
          onChange={handleChange('imageUrl')}
        />
      </label>
      <label className="register-form__field">
        <span className="register-form__label">Texte alternatif de l'image</span>
        <input
          className="register-form__input"
          placeholder="Description courte de l'illustration"
          value={form.imageAlt}
          onChange={handleChange('imageAlt')}
        />
      </label>
      {previewUrl ? (
        <div className="register-form__field">
          <span className="register-form__label">Aperçu</span>
          <img
            src={previewUrl}
            alt={form.imageAlt || form.title || 'Illustration du service'}
            className="h-48 w-full rounded-2xl border border-stone-200 bg-stone-50 object-contain p-4"
          />
        </div>
      ) : null}
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
