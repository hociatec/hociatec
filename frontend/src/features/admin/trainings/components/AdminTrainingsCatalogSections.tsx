import { Link } from 'react-router-dom';

import {
  type TrainingDto,
  type TrainingEnrollmentDto,
  type TrainingSessionDto,
} from '@/features/trainings/api/trainingsApi';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { AdminTrainingEnrollmentsTable } from './AdminTrainingEnrollmentsTable';

type AdminTrainingsCatalogSectionsProps = {
  trainings: TrainingDto[];
  sessions: TrainingSessionDto[];
  enrollments: TrainingEnrollmentDto[];
  onDeleteTraining: (training: TrainingDto) => void;
  onDeleteSession: (session: TrainingSessionDto) => void;
};

export const AdminTrainingsCatalogSections = ({
  trainings,
  sessions,
  enrollments,
  onDeleteTraining,
  onDeleteSession,
}: AdminTrainingsCatalogSectionsProps) => {
  const categoryName = (slug: string) =>
    trainings.find((training) => training.category === slug)?.categoryDetails?.name ?? '';

  return (
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
                      {training.shortDescription && (
                        <p className="muted">{training.shortDescription}</p>
                      )}
                    </td>
                    <td>{formatEuroCents(training.priceCents)}</td>
                    <td>{categoryName(training.category)}</td>
                    <td>{training.availableFormatDetails.map((format) => format.label).join(', ')}</td>
                    <td>{training.isActive ? 'Publiée' : 'Masquée'}</td>
                    <td>
                      <div className="catalog-admin-actions">
                        <Link
                          to={`/admin/trainings/${training.id}/edit`}
                          className="catalog-admin-actions__edit"
                        >
                          Modifier
                        </Link>
                        <button
                          type="button"
                          className="catalog-admin-actions__delete"
                          onClick={() => onDeleteTraining(training)}
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
            <p className="text-sm text-stone-500">
              Périodes réservables, formats, capacité par créneau et inscriptions.
            </p>
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
                          onClick={() => onDeleteSession(session)}
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
            <h2 className="text-xl font-semibold text-brand-900">Inscriptions</h2>
            <p className="text-sm text-stone-500">
              Suivi des paiements et statuts de participation.
            </p>
          </div>
          <Link to="/admin/trainings/enrollments" className="catalog-admin-actions__edit">
            Gérer les inscriptions
          </Link>
        </div>
        <AdminTrainingEnrollmentsTable enrollments={enrollments} />
      </section>
    </div>
  );
};
