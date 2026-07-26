import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  fetchAdminTrainingSessions,
  fetchAdminTrainings,
  saveAdminTrainingSession,
  type TrainingDto,
  type TrainingFormat,
} from '@/features/trainings/api/trainingsApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const emptyForm = {
  trainingId: 0,
  format: 'onsite' as TrainingFormat,
  startsAt: '',
  endsAt: '',
  dailyStartTime: '08:00',
  dailyEndTime: '20:00',
  includeWeekends: false,
  location: '',
  meetingUrl: '',
  capacity: 1,
  status: 'scheduled',
};

const toDateLocal = (value: string) => value.slice(0, 10);

export const TrainingSessionFormPage = () => {
  const { sessionId } = useParams();
  const isEdit = useMemo(() => Boolean(sessionId), [sessionId]);
  const navigate = useNavigate();

  useDocumentTitle(isEdit ? 'Admin - Modifier une session' : 'Admin - Nouvelle session');

  const [trainings, setTrainings] = useState<TrainingDto[]>([]);
  const [form, setForm] = useState(emptyForm);
  const [initialLoading, setInitialLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    const load = async () => {
      setInitialLoading(true);
      setError(null);

      try {
        const [trainingItems, sessionItems] = await Promise.all([
          fetchAdminTrainings(),
          isEdit ? fetchAdminTrainingSessions() : Promise.resolve([]),
        ]);

        setTrainings(trainingItems);

        if (isEdit && sessionId) {
          const session = sessionItems.find((item) => item.id === Number(sessionId));

          if (!session) {
            setError('Session introuvable.');
            return;
          }

          setForm({
            trainingId: session.training.id,
            format: session.format,
            startsAt: toDateLocal(session.startsAt),
            endsAt: toDateLocal(session.endsAt),
            dailyStartTime: session.dailyStartTime,
            dailyEndTime: session.dailyEndTime,
            includeWeekends: session.includeWeekends,
            location: session.location ?? '',
            meetingUrl: session.meetingUrl ?? '',
            capacity: session.capacity,
            status: session.status,
          });
        }
      } catch (err) {
        setError(getHttpErrorMessage(err, 'Impossible de charger les données de session.'));
      } finally {
        setInitialLoading(false);
      }
    };

    void load();
  }, [isEdit, sessionId]);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.trainingId) {
      setError('Choisissez une formation.');
      return;
    }

    if (!form.startsAt || !form.endsAt) {
      setError('Choisissez une date de début et une date de fin de disponibilité.');
      return;
    }

    setSaving(true);
    setError(null);
    setMessage(null);

    try {
      await saveAdminTrainingSession({
        ...form,
        startsAt: `${form.startsAt}T00:00:00`,
        endsAt: `${form.endsAt}T23:59:59`,
        location: form.format === 'onsite' ? form.location.trim() || null : null,
        meetingUrl: form.format === 'remote' ? form.meetingUrl.trim() || null : null,
      }, sessionId ? Number(sessionId) : undefined);
      setMessage(isEdit ? 'Session mise à jour.' : 'Session créée.');
      setTimeout(() => navigate('/admin/trainings/sessions'), 600);
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la session."));
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer size="admin"
      title={isEdit ? 'Modifier une session' : 'Nouvelle session'}
      headerActions={
        <button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/admin/trainings/sessions')}>
          Retour aux sessions
        </button>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading ? (
        <LoadingState>Chargement...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <label className="register-form__field">
            <span>Formation</span>
            <select value={form.trainingId} onChange={(event) => setForm((prev) => ({ ...prev, trainingId: Number(event.target.value) }))}>
              <option value={0}>Choisir...</option>
              {trainings.map((training) => (
                <option key={training.id} value={training.id}>{training.title}</option>
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
              <input type="date" value={form.startsAt} onChange={(event) => setForm((prev) => ({ ...prev, startsAt: event.target.value }))} required />
            </label>
            <label className="register-form__field">
              <span>Date de fin de disponibilité</span>
              <input type="date" value={form.endsAt} onChange={(event) => setForm((prev) => ({ ...prev, endsAt: event.target.value }))} required />
            </label>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="register-form__field">
              <span>Réservable chaque jour à partir de</span>
              <input type="time" value={form.dailyStartTime} onChange={(event) => setForm((prev) => ({ ...prev, dailyStartTime: event.target.value }))} required />
            </label>
            <label className="register-form__field">
              <span>Réservable chaque jour jusqu’à</span>
              <input type="time" value={form.dailyEndTime} onChange={(event) => setForm((prev) => ({ ...prev, dailyEndTime: event.target.value }))} required />
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
              <input value={form.location} onChange={(event) => setForm((prev) => ({ ...prev, location: event.target.value }))} />
            </label>
          ) : (
            <label className="register-form__field">
              <span>Lien de visioconférence</span>
              <input value={form.meetingUrl} onChange={(event) => setForm((prev) => ({ ...prev, meetingUrl: event.target.value }))} />
            </label>
          )}
          <label className="register-form__field">
            <span>Nombre maximum de participants par créneau</span>
            <input type="number" min={1} value={form.capacity} onChange={(event) => setForm((prev) => ({ ...prev, capacity: Number(event.target.value) }))} />
          </label>
          <button type="submit" className="register-form__submit" disabled={saving}>
            {saving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
