import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router';

import {
  fetchAdminTrainingEnrollments,
  updateAdminTrainingEnrollmentStatus,
  type TrainingEnrollmentDto,
  type TrainingEnrollmentStatus,
} from '@/features/trainings/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchDateTime } from '@/shared/lib/formatters';
import { adminTrainingQueryKeys } from '@/shared/lib/queryKeys';

export const TrainingEnrollmentsPage = () => {
  useDocumentTitle('Admin - Inscriptions formation');

  const queryClient = useQueryClient();
  const enrollmentsQuery = useQuery<TrainingEnrollmentDto[], Error>({
    queryKey: adminTrainingQueryKeys.enrollments(),
    queryFn: fetchAdminTrainingEnrollments,
  });
  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: TrainingEnrollmentStatus }) =>
      updateAdminTrainingEnrollmentStatus(id, status),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.enrollments() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.overview() });
    },
  });
  const enrollments = enrollmentsQuery.data ?? [];
  const error = enrollmentsQuery.error
    ? getHttpErrorMessage(enrollmentsQuery.error, 'Impossible de charger les inscriptions.')
    : statusMutation.error
      ? getHttpErrorMessage(statusMutation.error, 'Impossible de modifier le statut.')
      : null;

  const handleStatus = async (id: number, status: TrainingEnrollmentStatus) => {
    statusMutation.mutate({ id, status });
  };

  return (
    <PageContainer
      size="admin"
      title="Inscriptions aux formations"
      headerActions={
        <Link to="/admin/trainings" className="catalog-admin-actions__edit">
          Formations
        </Link>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <AdminListState
        loading={enrollmentsQuery.isLoading}
        isEmpty={enrollments.length === 0}
        loadingLabel="Chargement des inscriptions..."
        emptyLabel="Aucune inscription."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>Formation</th>
                <th>Créneau</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {enrollments.map((enrollment) => (
                <tr key={enrollment.id}>
                  <td>{enrollment.session.training.title}</td>
                  <td>{formatFrenchDateTime(enrollment.scheduledStartsAt)}</td>
                  <td>{enrollment.statusLabel}</td>
                  <td>
                    <select
                      className="select-filter"
                      value={enrollment.status}
                      onChange={(event) =>
                        void handleStatus(
                          enrollment.id,
                          event.target.value as TrainingEnrollmentStatus,
                        )
                      }
                    >
                      <option value="pending_payment">Paiement en attente</option>
                      <option value="paid">Payée</option>
                      <option value="confirmed">Confirmée</option>
                      <option value="completed">Terminée</option>
                      <option value="cancelled">Annulée</option>
                    </select>
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
