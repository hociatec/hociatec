import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  fetchAdminTrainingSessions,
  fetchAdminTrainings,
  saveAdminTrainingSession,
  type TrainingDto,
  type TrainingFormat,
} from '@/features/trainings/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatApiDateForDateInput } from '@/shared/lib/formatters';
import { useDelayedNavigation } from '@/shared/hooks/useDelayedNavigation';
import {
  TrainingSessionFormFields,
  type TrainingSessionFormState,
} from '@/features/admin/trainings/components/TrainingSessionFormFields';
import { adminTrainingQueryKeys } from '@/features/admin/trainings/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

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

export const TrainingSessionFormPage = () => {
  const { sessionId } = useParams();
  const parsedSessionId = parseNullablePositiveInteger(sessionId);
  const safeSessionId = parsedSessionId ?? 0;
  const isEdit = parsedSessionId !== null;
  const navigate = useNavigate();
  const navigateWithDelay = useDelayedNavigation(600);
  const queryClient = useQueryClient();

  useDocumentTitle(isEdit ? 'Admin - Modifier une session' : 'Admin - Nouvelle session');

  const [form, setForm] = useState(emptyForm);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const formQuery = useQuery({
    queryKey: adminTrainingQueryKeys.sessionForm(isEdit ? parsedSessionId : null),
    queryFn: async () => {
      const [trainings, sessions] = await Promise.all([
        fetchAdminTrainings(),
        isEdit ? fetchAdminTrainingSessions() : Promise.resolve([]),
      ]);
      return { trainings, sessions };
    },
  });
  const trainings: TrainingDto[] = formQuery.data?.trainings ?? [];

  useEffect(() => {
    if (!formQuery.data) return;
    if (isEdit) {
      const session = formQuery.data.sessions.find((item) => item.id === parsedSessionId);

          if (!session) {
            setError('Session introuvable.');
            return;
          }

          setForm({
            trainingId: session.training.id,
            format: session.format,
            startsAt: formatApiDateForDateInput(session.startsAt),
            endsAt: formatApiDateForDateInput(session.endsAt),
            dailyStartTime: session.dailyStartTime,
            dailyEndTime: session.dailyEndTime,
            includeWeekends: session.includeWeekends,
            location: session.location ?? '',
            meetingUrl: session.meetingUrl ?? '',
            capacity: session.capacity,
            status: session.status,
          });
        }
  }, [formQuery.data, isEdit, parsedSessionId]);

  const saveMutation = useMutation({
    mutationFn: () =>
      saveAdminTrainingSession(
        {
          ...form,
          startsAt: `${form.startsAt}T00:00:00`,
          endsAt: `${form.endsAt}T23:59:59`,
          location: form.format === 'onsite' ? form.location.trim() || null : null,
          meetingUrl: form.format === 'remote' ? form.meetingUrl.trim() || null : null,
        },
        isEdit ? safeSessionId : undefined,
      ),
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.sessions() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.enrollments() });
      setMessage(response.message ?? (isEdit ? 'La session a bien été mise à jour.' : 'La session a bien été créée.'));
      navigateWithDelay('/admin/trainings/sessions');
    },
    onError: (err) => setError(getHttpErrorMessage(err, "Impossible d'enregistrer la session.")),
  });

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

    setError(null);
    setMessage(null);
    saveMutation.mutate();
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
      {(error || formQuery.error) && (
        <FeedbackMessage>
          {error ?? getHttpErrorMessage(formQuery.error, 'Impossible de charger les données de session.')}
        </FeedbackMessage>
      )}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {formQuery.isLoading ? (
        <LoadingState>Chargement...</LoadingState>
      ) : (
        <form onSubmit={handleSubmit} className="register-form-card form-card-grid">
          <TrainingSessionFormFields trainings={trainings} form={form} setForm={setForm} />
          <button type="submit" className="register-form__submit" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </form>
      )}
    </PageContainer>
  );
};
