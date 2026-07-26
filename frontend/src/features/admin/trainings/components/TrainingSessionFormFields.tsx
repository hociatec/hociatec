import type { Dispatch, SetStateAction } from 'react';

import type { TrainingDto, TrainingFormat } from '@/features/trainings/api/trainingsApi';

export type TrainingSessionFormState = {
  trainingId: number;
  format: TrainingFormat;
  startsAt: string;
  endsAt: string;
  dailyStartTime: string;
  dailyEndTime: string;
  includeWeekends: boolean;
  location: string;
  meetingUrl: string;
  capacity: number;
  status: string;
};

type TrainingSessionFormFieldsProps = {
  trainings: TrainingDto[];
  form: TrainingSessionFormState;
  setForm: Dispatch<SetStateAction<TrainingSessionFormState>>;
};

export const TrainingSessionFormFields = ({
  trainings,
  form,
  setForm,
}: TrainingSessionFormFieldsProps) => (
  <>
    <label className="register-form__field">
      <span>Formation</span>
      <select
        value={form.trainingId}
        onChange={(event) => setForm((prev) => ({ ...prev, trainingId: Number(event.target.value) }))}
      >
        <option value={0}>Choisir...</option>
        {trainings.map((training) => (
          <option key={training.id} value={training.id}>
            {training.title}
          </option>
        ))}
      </select>
    </label>
    <label className="register-form__field">
      <span>Mode de formation</span>
      <select
        value={form.format}
        onChange={(event) => {
          const format = event.target.value as TrainingFormat;
          setForm((prev) => ({
            ...prev,
            format,
            location: format === 'onsite' ? prev.location : '',
            meetingUrl: format === 'remote' ? prev.meetingUrl : '',
          }));
        }}
      >
        <option value="onsite">Présentiel</option>
        <option value="remote">Distanciel</option>
      </select>
    </label>
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span>Date de début de disponibilité</span>
        <input
          type="date"
          value={form.startsAt}
          onChange={(event) => setForm((prev) => ({ ...prev, startsAt: event.target.value }))}
          required
        />
      </label>
      <label className="register-form__field">
        <span>Date de fin de disponibilité</span>
        <input
          type="date"
          value={form.endsAt}
          onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))}
          required
        />
      </label>
    </div>
    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span>Réservable chaque jour à partir de</span>
        <input
          type="time"
          value={form.dailyStartTime}
          onChange={(event) => setForm((prev) => ({ ...prev, dailyStartTime: event.target.value }))}
          required
        />
      </label>
      <label className="register-form__field">
        <span>Réservable chaque jour jusqu’à</span>
        <input
          type="time"
          value={form.dailyEndTime}
          onChange={(event) => setForm((prev) => ({ ...prev, dailyEndTime: event.target.value }))}
          required
        />
      </label>
    </div>
    <label className="flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-stone-700">
      <input
        type="checkbox"
        checked={form.includeWeekends}
        onChange={(event) => setForm((prev) => ({ ...prev, includeWeekends: event.target.checked }))}
        className="mt-1"
      />
      <span>
        <strong className="block text-brand-900">Autoriser les réservations le week-end</strong>
        <span>Si décoché, les clients ne pourront réserver que du lundi au vendredi.</span>
      </span>
    </label>
    {form.format === 'onsite' ? (
      <label className="register-form__field">
        <span>Adresse ou lieu du rendez-vous</span>
        <input
          value={form.location}
          onChange={(event) => setForm((prev) => ({ ...prev, location: event.target.value }))}
        />
      </label>
    ) : (
      <label className="register-form__field">
        <span>Lien de visioconférence</span>
        <input
          value={form.meetingUrl}
          onChange={(event) => setForm((prev) => ({ ...prev, meetingUrl: event.target.value }))}
        />
      </label>
    )}
    <label className="register-form__field">
      <span>Nombre maximum de participants par créneau</span>
      <input
        type="number"
        min={1}
        value={form.capacity}
        onChange={(event) => setForm((prev) => ({ ...prev, capacity: Number(event.target.value) }))}
      />
    </label>
  </>
);
