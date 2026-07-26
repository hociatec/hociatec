import type { Dispatch, SetStateAction } from 'react';

import type { TrainingCategoryDto, TrainingFormat } from '@/features/trainings/api/trainingsApi';

export type TrainingFormState = {
  title: string;
  shortDescription: string;
  objective: string;
  audience: string;
  category: string;
  durationMinutes: number;
  priceEuros: string;
  availableFormats: TrainingFormat[];
  isActive: boolean;
  roadmap: string;
};

type TrainingFormFieldsProps = {
  form: TrainingFormState;
  categories: TrainingCategoryDto[];
  setForm: Dispatch<SetStateAction<TrainingFormState>>;
  onFormatChange: (format: TrainingFormat, checked: boolean) => void;
};

export const TrainingFormFields = ({
  form,
  categories,
  setForm,
  onFormatChange,
}: TrainingFormFieldsProps) => (
  <>
    <label className="register-form__field">
      <span>Titre</span>
      <input
        value={form.title}
        onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))}
        required
      />
    </label>
    <label className="register-form__field">
      <span>Description courte</span>
      <textarea
        value={form.shortDescription}
        onChange={(event) => setForm((prev) => ({ ...prev, shortDescription: event.target.value }))}
      />
    </label>
    <label className="register-form__field">
      <span>Objectif</span>
      <textarea
        value={form.objective}
        onChange={(event) => setForm((prev) => ({ ...prev, objective: event.target.value }))}
      />
    </label>
    <label className="register-form__field">
      <span>Public concerné</span>
      <textarea
        value={form.audience}
        onChange={(event) => setForm((prev) => ({ ...prev, audience: event.target.value }))}
      />
    </label>
    <label className="register-form__field">
      <span>Catégorie</span>
      <select
        value={form.category}
        onChange={(event) => setForm((prev) => ({ ...prev, category: event.target.value }))}
      >
        {categories.map((category) => (
          <option key={category.id} value={category.slug}>
            {category.name}
          </option>
        ))}
      </select>
    </label>
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span>Durée en minutes</span>
        <input
          type="number"
          min={1}
          value={form.durationMinutes}
          onChange={(event) =>
            setForm((prev) => ({ ...prev, durationMinutes: Number(event.target.value) }))
          }
        />
      </label>
      <label className="register-form__field">
        <span>Prix en euros</span>
        <input
          type="number"
          min={0}
          step="0.01"
          value={form.priceEuros}
          onChange={(event) => setForm((prev) => ({ ...prev, priceEuros: event.target.value }))}
        />
      </label>
    </div>
    <fieldset className="register-form__field">
      <span>Formats disponibles</span>
      <label className="booking__checkbox">
        <input
          type="checkbox"
          checked={form.availableFormats.includes('onsite')}
          onChange={(event) => onFormatChange('onsite', event.target.checked)}
        />
        Présentiel
      </label>
      <label className="booking__checkbox">
        <input
          type="checkbox"
          checked={form.availableFormats.includes('remote')}
          onChange={(event) => onFormatChange('remote', event.target.checked)}
        />
        Distanciel
      </label>
    </fieldset>
    <label className="register-form__field">
      <span>Feuille de route, une étape par ligne</span>
      <textarea
        rows={6}
        value={form.roadmap}
        onChange={(event) => setForm((prev) => ({ ...prev, roadmap: event.target.value }))}
      />
    </label>
    <label className="booking__checkbox">
      <input
        type="checkbox"
        checked={form.isActive}
        onChange={(event) => setForm((prev) => ({ ...prev, isActive: event.target.checked }))}
      />
      Formation publiée
    </label>
  </>
);
