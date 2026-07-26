import { useCallback, useEffect, useState } from 'react';
import { useConfirm } from '@/shared/components/ui/confirm';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import {
  deleteAdminTraining,
  deleteAdminTrainingSession,
  fetchAdminTrainingCategories,
  fetchAdminTrainingEnrollments,
  fetchAdminTrainingSessions,
  fetchAdminTrainings,
  type TrainingCategoryDto,
  type TrainingDto,
  type TrainingEnrollmentDto,
  type TrainingSessionDto,
} from '@/features/trainings/api/trainingsApi';

export const useAdminTrainingsOverview = () => {
  const confirm = useConfirm();
  const [trainings, setTrainings] = useState<TrainingDto[]>([]);
  const [categories, setCategories] = useState<TrainingCategoryDto[]>([]);
  const [sessions, setSessions] = useState<TrainingSessionDto[]>([]);
  const [enrollments, setEnrollments] = useState<TrainingEnrollmentDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [trainingItems, sessionItems, enrollmentItems, categoryItems] = await Promise.all([
        fetchAdminTrainings(),
        fetchAdminTrainingSessions(),
        fetchAdminTrainingEnrollments(),
        fetchAdminTrainingCategories(),
      ]);
      setTrainings(trainingItems);
      setSessions(sessionItems);
      setEnrollments(enrollmentItems);
      setCategories(categoryItems);
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de charger le module formations.'));
    } finally {
      setLoading(false);
    }
  }, []);
  useEffect(() => {
    void load();
  }, [load]);
  const handleDelete = async (training: TrainingDto) => {
    if (
      !(await confirm({
        title: 'Supprimer la formation',
        description: `Supprimer "${training.title}" ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    setMessage(null);
    try {
      const response = await deleteAdminTraining(training.id);
      await load();
      setMessage(response.message ?? 'La formation a bien été supprimée.');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de supprimer la formation.'));
    }
  };
  const handleDeleteSession = async (session: TrainingSessionDto) => {
    if (
      !(await confirm({
        title: 'Supprimer la session',
        description: `Supprimer la session du ${session.startsAt} au ${session.endsAt} ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    setMessage(null);
    try {
      const response = await deleteAdminTrainingSession(session.id);
      await load();
      setMessage(response.message ?? 'La session a bien été supprimée.');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de supprimer la session.'));
    }
  };
  return {
    trainings,
    categories,
    sessions,
    enrollments,
    loading,
    error,
    message,
    handleDelete,
    handleDeleteSession,
  };
};
