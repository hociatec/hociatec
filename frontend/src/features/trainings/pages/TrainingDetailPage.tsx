import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  enrollTrainingSession,
  fetchPublicTraining,
  formatTrainingFormat,
  type TrainingDto,
  type TrainingSessionDto,
} from '@/features/trainings/api';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatFrenchDate, formatFrenchTime } from '@/shared/lib/formatters';

const formatDuration = (minutes: number) => {
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  if (hours > 0 && remainingMinutes > 0) return `${hours} h ${remainingMinutes} min`;
  if (hours > 0) return `${hours} h`;
  return `${minutes} min`;
};

const calculateEndTime = (date: string, time: string, durationMinutes: number) => {
  if (!date || !time) return null;

  const start = new Date(`${date}T${time}:00`);
  if (Number.isNaN(start.getTime())) return null;

  const end = new Date(start.getTime() + durationMinutes * 60_000);
  return formatFrenchTime(end.toISOString());
};

const calculateLatestStartTime = (dailyEndTime: string, durationMinutes: number) => {
  const end = new Date(`2000-01-01T${dailyEndTime}:00`);
  if (Number.isNaN(end.getTime())) return dailyEndTime;

  const latestStart = new Date(end.getTime() - durationMinutes * 60_000);
  return formatFrenchTime(latestStart.toISOString());
};

const isWeekendDate = (date: string) => {
  const parsed = new Date(`${date}T12:00:00`);
  if (Number.isNaN(parsed.getTime())) return false;

  return parsed.getDay() === 0 || parsed.getDay() === 6;
};

export const TrainingDetailPage = () => {
  const { slug = '' } = useParams();
  const navigate = useNavigate();
  const { status } = useAuth();
  const [training, setTraining] = useState<TrainingDto | null>(null);
  const [sessions, setSessions] = useState<TrainingSessionDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [submittingId, setSubmittingId] = useState<number | null>(null);
  const [slotForms, setSlotForms] = useState<Record<number, { date: string; time: string }>>({});

  useDocumentTitle(training ? training.title : 'Formation');

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchPublicTraining(slug)
      .then((data) => {
        setTraining(data.training);
        setSessions(data.sessions);
      })
      .catch((err: Error) => setError(err.message || 'Formation introuvable.'))
      .finally(() => setLoading(false));
  }, [slug]);

  const handleEnroll = async (session: TrainingSessionDto) => {
    if (status !== 'authenticated') {
      navigate('/login', { state: { redirectTo: `/formations/${slug}` } });
      return;
    }

    const slot = slotForms[session.id];
    if (!slot?.date || !slot?.time) {
      setMessage('Choisissez une date et une heure de début.');
      return;
    }

    if (!session.includeWeekends && isWeekendDate(slot.date)) {
      setMessage('Cette session est réservable uniquement du lundi au vendredi.');
      return;
    }

    setSubmittingId(session.id);
    setMessage(null);
    try {
      const enrollment = await enrollTrainingSession(session.id, `${slot.date}T${slot.time}:00`);
      if (enrollment.checkoutUrl) {
        window.location.href = enrollment.checkoutUrl;
        return;
      }
      setMessage('Inscription enregistrée. Retrouvez le suivi dans votre espace formations.');
    } catch (err) {
      setMessage(err instanceof Error ? err.message : 'Inscription impossible.');
    } finally {
      setSubmittingId(null);
    }
  };

  return (
    <SiteLayout headerVariant="light">
      <main className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
        {loading ? (
          <div className="rounded-xl border border-brand-100 bg-white p-8 text-center text-stone-600" aria-hidden="true">Chargement...</div>
        ) : error || !training ? (
          <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center text-red-700">{error}</div>
        ) : (
          <>
            <header className="rounded-xl border border-brand-100 bg-white p-8 shadow-sm">
              <Link to="/formations" className="text-sm font-semibold text-stone-600 hover:text-brand-900">
                ← Toutes les formations
              </Link>
              <h1 className="mt-4 text-4xl font-semibold tracking-tight text-brand-900">{training.title}</h1>
              <p className="mt-4 max-w-3xl text-base leading-7 text-stone-600">
                {training.objective || training.shortDescription || 'Formation accompagnée avec feuille de route.'}
              </p>
              <div className="mt-5 flex flex-wrap gap-2">
                {training.availableFormats.map((format) => (
                  <span key={format} className="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">
                    {formatTrainingFormat(format)}
                  </span>
                ))}
                <span className="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-stone-700">
                  {formatEuroCents(training.priceCents)}
                </span>
              </div>
            </header>

            <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
              <article className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-brand-900">Feuille de route</h2>
                <ol className="mt-5 grid gap-3">
                  {training.roadmap.map((item) => (
                    <li key={item.id} className="flex gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-stone-700">
                      <strong className="text-brand-900">{item.position}.</strong>
                      <span>{item.title}</span>
                    </li>
                  ))}
                </ol>
              </article>

              <article className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-brand-900">Sessions disponibles</h2>
                {message && <div className="mt-4 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">{message}</div>}
                {sessions.length === 0 ? (
                  <p className="mt-5 text-sm text-stone-600">Aucune session planifiée pour le moment.</p>
                ) : (
                  <div className="mt-5 grid gap-3">
                    {sessions.map((session) => (
                      <div key={session.id} className="rounded-2xl border border-brand-100 p-4">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                          <div>
                            {(() => {
                              const latestStartTime = calculateLatestStartTime(session.dailyEndTime, training.durationMinutes);

                              return (
                                <>
                            <strong className="text-brand-900">
                              Du {formatFrenchDate(session.startsAt) ?? '-'} au {formatFrenchDate(session.endsAt) ?? '-'}
                            </strong>
                            <p className="mt-1 text-sm text-stone-600">
                              {formatTrainingFormat(session.format)} · {session.capacity} participant(s) maximum par créneau · durée {formatDuration(training.durationMinutes)}
                            </p>
                            <p className="mt-1 text-sm text-stone-600">
                              Début possible de {session.dailyStartTime} à {latestStartTime}, fin au plus tard à {session.dailyEndTime}
                            </p>
                            <p className="mt-1 text-sm text-stone-600">
                              {session.includeWeekends ? 'Réservation possible week-end inclus' : 'Réservation du lundi au vendredi uniquement'}
                            </p>
                            <p className="mt-1 text-sm text-stone-600">
                              {session.format === 'remote' ? 'Lien transmis après confirmation' : session.location || 'Lieu à confirmer'}
                            </p>
                            {(() => {
                              const slot = slotForms[session.id];
                              const endTime = calculateEndTime(slot?.date ?? '', slot?.time ?? '', training.durationMinutes);

                              return endTime ? (
                                <p className="mt-3 rounded-xl bg-brand-50 px-3 py-2 text-sm font-medium text-stone-800">
                                  Fin calculée : {endTime}
                                </p>
                              ) : null;
                            })()}
                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                              <label className="grid gap-1 text-sm text-stone-700">
                                <span>Date souhaitée</span>
                                <input
                                  type="date"
                                  min={session.startsAt.slice(0, 10)}
                                  max={session.endsAt.slice(0, 10)}
                                  value={slotForms[session.id]?.date ?? ''}
                                  onChange={(event) => setSlotForms((prev) => ({
                                    ...prev,
                                    [session.id]: { date: event.target.value, time: prev[session.id]?.time ?? session.dailyStartTime },
                                  }))}
                                  className="rounded-xl border border-brand-200 px-3 py-2"
                                />
                              </label>
                              <label className="grid gap-1 text-sm text-stone-700">
                                <span>Heure de début souhaitée</span>
                                <input
                                  type="time"
                                  min={session.dailyStartTime}
                                  max={latestStartTime}
                                  value={slotForms[session.id]?.time ?? ''}
                                  onChange={(event) => setSlotForms((prev) => ({
                                    ...prev,
                                    [session.id]: { date: prev[session.id]?.date ?? session.startsAt.slice(0, 10), time: event.target.value },
                                  }))}
                                  className="rounded-xl border border-brand-200 px-3 py-2"
                                />
                              </label>
                            </div>
                                </>
                              );
                            })()}
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
                    ))}
                  </div>
                )}
              </article>
            </section>
          </>
        )}
      </main>
    </SiteLayout>
  );
};
