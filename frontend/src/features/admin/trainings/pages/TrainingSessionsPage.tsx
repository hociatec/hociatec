import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  deleteAdminTrainingSession,
  fetchAdminTrainingSessions,
  formatTrainingFormat,
  type TrainingSessionDto,
} from '@/features/trainings/api/trainingsApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const TrainingSessionsPage = () => {
  useDocumentTitle('Admin - Sessions de formation');

  const [sessions, setSessions] = useState<TrainingSessionDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const confirm = useConfirm();

  const load = async () => {
    setLoading(true);
    setError(null);

    try {
      setSessions(await fetchAdminTrainingSessions());
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de charger les sessions.'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

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

    try {
      await deleteAdminTrainingSession(session.id);
      await load();
      setMessage('Session supprimée.');
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la session.'));
    }
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
        loading={loading}
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
                  <td>{formatTrainingFormat(session.format)}</td>
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
