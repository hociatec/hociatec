import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
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
} from '@/features/trainings/publicApi';
import { adminTrainingQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminTrainingsOverview = () => {
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [message, setMessage] = useState<string | null>(null);
  const overviewQuery = useQuery<{
    trainings: TrainingDto[];
    categories: TrainingCategoryDto[];
    sessions: TrainingSessionDto[];
    enrollments: TrainingEnrollmentDto[];
  }, Error>({
    queryKey: adminTrainingQueryKeys.overview(),
    queryFn: async () => {
      const [trainingItems, sessionItems, enrollmentItems, categoryItems] = await Promise.all([
        fetchAdminTrainings(),
        fetchAdminTrainingSessions(),
        fetchAdminTrainingEnrollments(),
        fetchAdminTrainingCategories(),
      ]);

      return {
        trainings: trainingItems,
        sessions: sessionItems,
        enrollments: enrollmentItems,
        categories: categoryItems,
      };
    },
  });
  const invalidateTrainingLists = () => {
    void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.overview() });
    void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.trainings() });
    void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.sessions() });
  };
  const deleteTrainingMutation = useMutation({
    mutationFn: deleteAdminTraining,
    onSuccess: (response) => {
      invalidateTrainingLists();
      setMessage(response.message ?? 'La formation a bien été supprimée.');
    },
  });
  const deleteSessionMutation = useMutation({
    mutationFn: deleteAdminTrainingSession,
    onSuccess: (response) => {
      invalidateTrainingLists();
      setMessage(response.message ?? 'La session a bien été supprimée.');
    },
  });
  const error =
    overviewQuery.error
      ? getHttpErrorMessage(overviewQuery.error, 'Impossible de charger le module formations.')
      : deleteTrainingMutation.error
        ? getHttpErrorMessage(deleteTrainingMutation.error, 'Impossible de supprimer la formation.')
        : deleteSessionMutation.error
          ? getHttpErrorMessage(deleteSessionMutation.error, 'Impossible de supprimer la session.')
          : null;
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
    deleteTrainingMutation.mutate(training.id);
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
    deleteSessionMutation.mutate(session.id);
  };
  return {
    trainings: overviewQuery.data?.trainings ?? [],
    categories: overviewQuery.data?.categories ?? [],
    sessions: overviewQuery.data?.sessions ?? [],
    enrollments: overviewQuery.data?.enrollments ?? [],
    loading: overviewQuery.isLoading,
    error,
    message,
    handleDelete,
    handleDeleteSession,
  };
};
