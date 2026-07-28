import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';

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
import {
  TrainingSessionFormFields,
  type TrainingSessionFormState,
} from '@/features/admin/trainings/components/TrainingSessionFormFields';

const emptyForm: TrainingSessionFormState = {
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
  const isEdit = Boolean(sessionId);
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
      const response = await saveAdminTrainingSession(
        {
          ...form,
          startsAt: `${form.startsAt}T00:00:00`,
          endsAt: `${form.endsAt}T23:59:59`,
          location: form.format === 'onsite' ? form.location.trim() || null : null,
          meetingUrl: form.format === 'remote' ? form.meetingUrl.trim() || null : null,
        },
        sessionId ? Number(sessionId) : undefined,
      );
      setMessage(response.message ?? (isEdit ? 'La session a bien été mise à jour.' : 'La session a bien été créée.'));
      setTimeout(() => navigate('/admin/trainings/sessions'), 600);
    } catch (err) {
      setError(getHttpErrorMessage(err, "Impossible d'enregistrer la session."));
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageContainer
      size="admin"
      title={isEdit ? 'Modifier une session' : 'Nouvelle session'}
      headerActions={
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => navigate('/admin/trainings/sessions')}
        >
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
          <TrainingSessionFormFields trainings={trainings} form={form} setForm={setForm} />
          <button type="submit" className="register-form__submit" disabled={saving}>
            {saving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
