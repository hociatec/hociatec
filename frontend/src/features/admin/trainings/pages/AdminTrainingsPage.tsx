import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import {
  deleteAdminTraining,
  deleteAdminTrainingSession,
  fetchAdminTrainingEnrollments,
  fetchAdminTrainingSessions,
  fetchAdminTrainingCategories,
  fetchAdminTrainings,
  formatTrainingEnrollmentStatus,
  formatTrainingCategory,
  formatTrainingFormat,
  type TrainingDto,
  type TrainingCategoryDto,
  type TrainingEnrollmentDto,
  type TrainingSessionDto,
} from '@/features/trainings/api/trainingsApi';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents, formatFrenchDateTime, formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const AdminTrainingsPage = () => {
  useDocumentTitle('Admin - Formations');

  const [trainings, setTrainings] = useState<TrainingDto[]>([]);
  const [categories, setCategories] = useState<TrainingCategoryDto[]>([]);
  const [sessions, setSessions] = useState<TrainingSessionDto[]>([]);
  const [enrollments, setEnrollments] = useState<TrainingEnrollmentDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const confirm = useConfirm();

  const load = async () => {
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
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de charger le module formations.'));
    } finally {
      setLoading(false);
    }
  };

  const categoryName = (slug: string) => categories.find((category) => category.slug === slug)?.name ?? formatTrainingCategory(slug);

  useEffect(() => {
    void load();
  }, []);

  const handleDelete = async (training: TrainingDto) => {
    const confirmed = await confirm({
      title: 'Supprimer la formation',
      description: `Supprimer "${training.title}" ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deleteAdminTraining(training.id);
      await load();
      setMessage('Formation supprimée.');
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la formation.'));
    }
  };

  const handleDeleteSession = async (session: TrainingSessionDto) => {
    const confirmed = await confirm({
      title: 'Supprimer la session',
      description: `Supprimer la session "${session.training.title}" du ${formatOptionalFrenchDate(session.startsAt)} au ${formatOptionalFrenchDate(session.endsAt)} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deleteAdminTrainingSession(session.id);
      await load();
      setMessage('Session supprimée.');
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la session.'));
    }
  };

  return (
    <PageContainer size="admin"
      title="Formations"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <PrimaryLink to="/admin/trainings/new">
            Nouvelle formation
          </PrimaryLink>
          <Link to="/admin/trainings/sessions" className="catalog-admin-actions__edit">
            Gérer les sessions
          </Link>
          <Link to="/admin/trainings/sessions/new" className="catalog-admin-actions__edit">
            Nouvelle session
          </Link>
          <Link to="/admin/trainings/enrollments" className="catalog-admin-actions__edit">
            Inscriptions
          </Link>
          <Link to="/admin/trainings/categories" className="catalog-admin-actions__edit">
            Catégories
          </Link>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {trainings.length} formation{trainings.length > 1 ? 's' : ''}, {sessions.length} session{sessions.length > 1 ? 's' : ''}, {enrollments.length} inscription{enrollments.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-stone-500">
          Vue complète du module formation. La création et la modification restent séparées dans des pages dédiées.
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={false}
        loadingLabel="Chargement du module formations..."
        emptyLabel=""
      >
        <div className="space-y-8">
          <section>
            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
              <div>
                <h2 className="text-xl font-semibold text-brand-900">Formations</h2>
                <p className="text-sm text-stone-500">Catalogue, prix, formats et publication.</p>
              </div>
              <div className="flex flex-wrap gap-3">
                <Link to="/admin/trainings/categories" className="catalog-admin-actions__edit">
                  Catégories
                </Link>
                <Link to="/admin/trainings/new" className="catalog-admin-actions__edit">
                  Nouvelle formation
                </Link>
              </div>
            </div>
            <AdminListState
              loading={false}
              isEmpty={trainings.length === 0}
              loadingLabel=""
              emptyLabel="Aucune formation enregistrée."
            >
              <AdminTableShell>
                <table className="catalog-admin-table">
                  <thead>
                    <tr>
                      <th>Titre</th>
                      <th>Prix</th>
                      <th>Catégorie</th>
                      <th>Formats</th>
                      <th>Statut</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {trainings.map((training) => (
                      <tr key={training.id}>
                        <td>
                          <strong>{training.title}</strong>
                          {training.shortDescription && <p className="muted">{training.shortDescription}</p>}
                        </td>
                        <td>{formatEuroCents(training.priceCents)}</td>
                        <td>{categoryName(training.category)}</td>
                        <td>{training.availableFormats.map(formatTrainingFormat).join(', ')}</td>
                        <td>{training.isActive ? 'Publiée' : 'Masquée'}</td>
                        <td>
                          <div className="catalog-admin-actions">
                            <Link to={`/admin/trainings/${training.id}/edit`} className="catalog-admin-actions__edit">
                              Modifier
                            </Link>
                            <button
                              type="button"
                              className="catalog-admin-actions__delete"
                              onClick={() => void handleDelete(training)}
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
          </section>

          <section>
            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
              <div>
                <h2 className="text-xl font-semibold text-brand-900">Sessions</h2>
                <p className="text-sm text-stone-500">Périodes réservables, formats, capacité par créneau et inscriptions.</p>
              </div>
              <Link to="/admin/trainings/sessions/new" className="catalog-admin-actions__edit">
                Nouvelle session
              </Link>
            </div>
            <AdminListState
              loading={false}
              isEmpty={sessions.length === 0}
              loadingLabel=""
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
                          Du {formatOptionalFrenchDate(session.startsAt)} au {formatOptionalFrenchDate(session.endsAt)}
                          <p className="muted">Chaque jour de {session.dailyStartTime} à {session.dailyEndTime}</p>
                          <p className="muted">{session.includeWeekends ? 'Week-end inclus' : 'Hors week-end'}</p>
                        </td>
                        <td>{formatTrainingFormat(session.format)}</td>
                        <td>{session.capacity} par créneau</td>
                        <td>
                          <div className="catalog-admin-actions">
                            <Link to={`/admin/trainings/sessions/${session.id}/edit`} className="catalog-admin-actions__edit">
                              Modifier
                            </Link>
                            <button type="button" className="catalog-admin-actions__delete" onClick={() => void handleDeleteSession(session)}>
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
          </section>

          <section>
            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
              <div>
                <h2 className="text-xl font-semibold text-brand-900">Inscriptions</h2>
                <p className="text-sm text-stone-500">Suivi des paiements et statuts de participation.</p>
              </div>
              <Link to="/admin/trainings/enrollments" className="catalog-admin-actions__edit">
                Gérer les inscriptions
              </Link>
            </div>
            <AdminListState
              loading={false}
              isEmpty={enrollments.length === 0}
              loadingLabel=""
              emptyLabel="Aucune inscription."
            >
              <AdminTableShell>
                <table className="catalog-admin-table">
                  <thead>
                    <tr>
                      <th>Formation</th>
                      <th>Créneau</th>
                      <th>Prix</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    {enrollments.map((enrollment) => (
                      <tr key={enrollment.id}>
                        <td>{enrollment.session.training.title}</td>
                        <td>{formatFrenchDateTime(enrollment.scheduledStartsAt)}</td>
                        <td>{formatEuroCents(enrollment.priceCents)}</td>
                        <td>{formatTrainingEnrollmentStatus(enrollment.status)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </AdminTableShell>
            </AdminListState>
          </section>
        </div>
      </AdminListState>
    </PageContainer>
  );
};
