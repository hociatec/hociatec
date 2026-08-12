import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router';

import {
  deleteAdminTrainingSession,
  fetchAdminTrainingSessionsPage,
  type TrainingSessionDto,
} from '@/features/trainings/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { adminTrainingQueryKeys } from '@/features/admin/trainings/queryKeys';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginatedResult } from '@/shared/types/api';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const TrainingSessionsPage = () => {
  useDocumentTitle('Admin - Sessions de formation');

  const [message, setMessage] = useState<string | null>(null);
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const sessionsQuery = useQuery<PaginatedResult<TrainingSessionDto>, Error>({
    queryKey: [...adminTrainingQueryKeys.sessions(), { page }],
    queryFn: () => fetchAdminTrainingSessionsPage(page, 10),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteAdminTrainingSession,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.sessions() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.enrollments() });
      setMessage(response.message ?? 'La session a bien été supprimée.');
    },
  });
  const sessions = sessionsQuery.data?.items ?? [];
  const pagination = sessionsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const error = sessionsQuery.error
    ? getHttpErrorMessage(sessionsQuery.error, 'Impossible de charger les sessions.')
    : deleteMutation.error
      ? getHttpErrorMessage(deleteMutation.error, 'Impossible de supprimer la session.')
      : null;

  useEffect(() => {
    const next = new URLSearchParams();
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, setSearchParams]);

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
        <PaginationControls
          page={pagination.page}
          total={pagination.total}
          totalLabel="session"
          totalPages={pagination.totalPages}
          onPageChange={setPage}
        />
      </AdminListState>
    </PageContainer>
  );
};
