import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router';

import {
  deleteAdminTrainingSession,
  fetchAdminTrainingSessions,
  type TrainingSessionDto,
} from '@/features/trainings/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { adminTrainingQueryKeys } from '@/shared/lib/queryKeys';

export const TrainingSessionsPage = () => {
  useDocumentTitle('Admin - Sessions de formation');

  const [message, setMessage] = useState<string | null>(null);
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const sessionsQuery = useQuery<TrainingSessionDto[], Error>({
    queryKey: adminTrainingQueryKeys.sessions(),
    queryFn: fetchAdminTrainingSessions,
  });
  const deleteMutation = useMutation({
    mutationFn: deleteAdminTrainingSession,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.sessions() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.overview() });
      setMessage(response.message ?? 'La session a bien été supprimée.');
    },
  });
  const sessions = sessionsQuery.data ?? [];
  const error = sessionsQuery.error
    ? getHttpErrorMessage(sessionsQuery.error, 'Impossible de charger les sessions.')
    : deleteMutation.error
      ? getHttpErrorMessage(deleteMutation.error, 'Impossible de supprimer la session.')
      : null;

  const handleDelete = async (session: TrainingSessionDto) => {
    const confirmed = await confirm({
      title: 'Supprimer la session',
      description: `Supprimer la session "${session.training.title}" du ${formatOptionalFrenchDate(session.startsAt)} au ${formatOptionalFrenchDate(session.endsAt)} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    deleteMutation.mutate(session.id);
  };

  return (
    <PageContainer
      size="admin"
      title="Sessions de formation"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link to="/admin/trainings" className="catalog-admin-actions__edit">
            Formations
          </Link>
          <PrimaryLink to="/admin/trainings/sessions/new">Nouvelle session</PrimaryLink>
        </div>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={sessionsQuery.isLoading}
        isEmpty={sessions.length === 0}
        loadingLabel="Chargement des sessions..."
        emptyLabel="Aucune session programmée."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>Formation</th>
                <th>Période réservable</th>
                <th>Format</th>
                <th>Participants</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {sessions.map((session) => (
                <tr key={session.id}>
                  <td>{session.training.title}</td>
                  <td>
                    Du {formatOptionalFrenchDate(session.startsAt)} au{' '}
                    {formatOptionalFrenchDate(session.endsAt)}
                    <p className="muted">
                      Chaque jour de {session.dailyStartTime} à {session.dailyEndTime}
                    </p>
                    <p className="muted">
                      {session.includeWeekends ? 'Week-end inclus' : 'Hors week-end'}
                    </p>
                  </td>
                  <td>{session.formatLabel}</td>
                  <td>{session.capacity} par créneau</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/trainings/sessions/${session.id}/edit`}
                        className="catalog-admin-actions__edit"
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(session)}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
