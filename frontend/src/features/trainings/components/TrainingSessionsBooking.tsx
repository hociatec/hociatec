import type { TrainingDto, TrainingSessionDto } from '@/features/trainings/api/trainingsApi';
import {
  calculateEndTime,
  calculateLatestStartTime,
  formatTrainingDuration,
} from '@/features/trainings/lib/trainingDetail';
import { formatApiDateForDateInput, formatFrenchDate } from '@/shared/lib/formatters';

type SlotForms = Record<number, { date: string; time: string }>;
type UpdateSlot = (
  sessionId: number,
  field: 'date' | 'time',
  value: string,
  defaults?: Partial<{ date: string; time: string }>,
) => void;

type TrainingSessionsBookingProps = {
  training: TrainingDto;
  sessions: TrainingSessionDto[];
  message: string | null;
  submittingId: number | null;
  slotForms: SlotForms;
  updateSlot: UpdateSlot;
  handleEnroll: (session: TrainingSessionDto) => Promise<void>;
};

export const TrainingSessionsBooking = ({
  training,
  sessions,
  message,
  submittingId,
  slotForms,
  updateSlot,
  handleEnroll,
}: TrainingSessionsBookingProps) => (
  <article className="rounded-xl border border-brand-100 bg-white p-6">
    <h2 className="text-xl font-semibold text-brand-900">Sessions disponibles</h2>
    {message && (
      <div className="mt-4 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
        {message}
      </div>
    )}
    {sessions.length === 0 ? (
      <p className="mt-5 text-sm text-stone-600">Aucune session planifiée pour le moment.</p>
    ) : (
      <div className="mt-5 grid gap-3">
        {sessions.map((session) => {
          const latestStartTime = calculateLatestStartTime(
            session.dailyEndTime,
            training.durationMinutes,
          );
          const slot = slotForms[session.id];
          const endTime = calculateEndTime(
            slot?.date ?? '',
            slot?.time ?? '',
            training.durationMinutes,
          );

          return (
            <div key={session.id} className="rounded-2xl border border-brand-100 p-4">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <strong className="text-brand-900">
                    Du {formatFrenchDate(session.startsAt) ?? '-'} au{' '}
                    {formatFrenchDate(session.endsAt) ?? '-'}
                  </strong>
                  <p className="mt-1 text-sm text-stone-600">
                    {session.formatLabel} · {session.capacity} participant(s) maximum par créneau ·
                    durée {formatTrainingDuration(training.durationMinutes)}
                  </p>
                  <p className="mt-1 text-sm text-stone-600">
                    Début possible de {session.dailyStartTime} à {latestStartTime}, fin au plus tard
                    à {session.dailyEndTime}
                  </p>
                  <p className="mt-1 text-sm text-stone-600">
                    {session.includeWeekends
                      ? 'Réservation possible week-end inclus'
                      : 'Réservation du lundi au vendredi uniquement'}
                  </p>
                  <p className="mt-1 text-sm text-stone-600">
                    {session.format === 'remote'
                      ? 'Lien transmis après confirmation'
                      : session.location || 'Lieu à confirmer'}
                  </p>
                  {endTime && (
                    <p className="mt-3 rounded-xl bg-brand-50 px-3 py-2 text-sm font-medium text-stone-800">
                      Fin calculée : {endTime}
                    </p>
                  )}
                  <div className="mt-4 grid gap-3 sm:grid-cols-2">
                    <label className="grid gap-1 text-sm text-stone-700">
                      <span>Date souhaitée</span>
                      <input
                        type="date"
                        min={formatApiDateForDateInput(session.startsAt)}
                        max={formatApiDateForDateInput(session.endsAt)}
                        value={slot?.date ?? ''}
                        onChange={(event) =>
                          updateSlot(session.id, 'date', event.target.value, {
                            time: session.dailyStartTime,
                          })
                        }
                        className="rounded-xl border border-brand-200 px-3 py-2"
                      />
                    </label>
                    <label className="grid gap-1 text-sm text-stone-700">
                      <span>Heure de début souhaitée</span>
                      <input
                        type="time"
                        min={session.dailyStartTime}
                        max={latestStartTime}
                        value={slot?.time ?? ''}
                        onChange={(event) =>
                          updateSlot(session.id, 'time', event.target.value, {
                            date: formatApiDateForDateInput(session.startsAt),
                          })
                        }
                        className="rounded-xl border border-brand-200 px-3 py-2"
                      />
                    </label>
                  </div>
                </div>
                <button
                  type="button"
                  className="rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                  disabled={submittingId === session.id}
                  onClick={() => void handleEnroll(session)}
                >
                  {submittingId === session.id ? 'Inscription...' : 'Réserver'}
                </button>
              </div>
            </div>
          );
        })}
      </div>
    )}
  </article>
);
