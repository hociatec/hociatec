import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  fetchAdminTrainingEnrollments,
  formatTrainingEnrollmentStatus,
  updateAdminTrainingEnrollmentStatus,
  type TrainingEnrollmentDto,
  type TrainingEnrollmentStatus,
} from '@/features/trainings/api/trainingsApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchDateTime } from '@/shared/lib/formatters';

export const TrainingEnrollmentsPage = () => {
  useDocumentTitle('Admin - Inscriptions formation');

  const [enrollments, setEnrollments] = useState<TrainingEnrollmentDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = async () => {
    setLoading(true);
    setError(null);

    try {
      setEnrollments(await fetchAdminTrainingEnrollments());
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de charger les inscriptions.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const handleStatus = async (id: number, status: TrainingEnrollmentStatus) => {
    try {
      await updateAdminTrainingEnrollmentStatus(id, status);
      await load();
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de modifier le statut.'));
    }
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
        loading={loading}
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
                  <td>{formatTrainingEnrollmentStatus(enrollment.status)}</td>
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
