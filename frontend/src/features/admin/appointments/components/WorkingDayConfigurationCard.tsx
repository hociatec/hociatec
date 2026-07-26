import type { WorkingDay } from '@/features/appointments/types/appointments';

const DAY_LABELS: Record<number, string> = {
  0: 'Lundi',
  1: 'Mardi',
  2: 'Mercredi',
  3: 'Jeudi',
  4: 'Vendredi',
  5: 'Samedi',
  6: 'Dimanche',
};

type WorkingDayConfigurationCardProps = {
  day: WorkingDay;
  updateDay: (dayOfWeek: number, updater: (current: WorkingDay) => WorkingDay) => void;
};

export const WorkingDayConfigurationCard = ({
  day,
  updateDay,
}: WorkingDayConfigurationCardProps) => (
  <div className="register-form-card form-card-grid">
    <label className="booking__checkbox">
      <input
        type="checkbox"
        checked={day.isWorkingDay}
        onChange={(event) =>
          updateDay(day.dayOfWeek, (current) => ({
            ...current,
            isWorkingDay: event.target.checked,
            startTime: event.target.checked ? (current.startTime ?? '09:00') : null,
            endTime: event.target.checked ? (current.endTime ?? '18:00') : null,
            breaks: event.target.checked ? (current.breaks ?? []) : [],
          }))
        }
      />
      <strong>{DAY_LABELS[day.dayOfWeek]}</strong>
    </label>

    {day.isWorkingDay && (
      <>
        <div className="grid gap-4 md:grid-cols-2">
          <label className="register-form__field">
            <span className="register-form__label">Début</span>
            <input
              type="time"
              className="register-form__input"
              value={day.startTime ?? ''}
              onChange={(event) =>
                updateDay(day.dayOfWeek, (current) => ({
                  ...current,
                  startTime: event.target.value || null,
                }))
              }
            />
          </label>
          <label className="register-form__field">
            <span className="register-form__label">Fin</span>
            <input
              type="time"
              className="register-form__input"
              value={day.endTime ?? ''}
              onChange={(event) =>
                updateDay(day.dayOfWeek, (current) => ({
                  ...current,
                  endTime: event.target.value || null,
                }))
              }
            />
          </label>
        </div>

        <div className="grid gap-2">
          <span className="register-form__label">Pauses</span>
          {day.breaks.length === 0 && <p className="muted">Aucune pause définie.</p>}
          {day.breaks.map((pause, index) => (
            <div key={`${day.dayOfWeek}-${index}`} className="flex flex-wrap items-center gap-3">
              <input
                type="time"
                className="register-form__input"
                value={pause.start}
                onChange={(event) =>
                  updateDay(day.dayOfWeek, (current) => ({
                    ...current,
                    breaks: current.breaks.map((slot, idx) =>
                      idx === index ? { ...slot, start: event.target.value } : slot,
                    ),
                  }))
                }
              />
              <span>à</span>
              <input
                type="time"
                className="register-form__input"
                value={pause.end}
                onChange={(event) =>
                  updateDay(day.dayOfWeek, (current) => ({
                    ...current,
                    breaks: current.breaks.map((slot, idx) =>
                      idx === index ? { ...slot, end: event.target.value } : slot,
                    ),
                  }))
                }
              />
              <button
                type="button"
                className="catalog-admin-actions__delete"
                onClick={() =>
                  updateDay(day.dayOfWeek, (current) => ({
                    ...current,
                    breaks: current.breaks.filter((_, idx) => idx !== index),
                  }))
                }
              >
                Supprimer
              </button>
            </div>
          ))}
          <button
            type="button"
            className="catalog-admin-actions__edit w-fit"
            onClick={() =>
              updateDay(day.dayOfWeek, (current) => ({
                ...current,
                breaks: [
                  ...current.breaks,
                  { start: current.startTime ?? '12:00', end: current.endTime ?? '13:00' },
                ],
              }))
            }
          >
            Ajouter une pause
          </button>
        </div>
      </>
    )}
  </div>
);
