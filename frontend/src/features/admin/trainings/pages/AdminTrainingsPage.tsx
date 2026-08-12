import { Link } from 'react-router';
import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import { PageContainer } from '@/shared/components/layout/PageContainer';
import {
  AdminListState,
  AdminMetricCard,
  AdminMetricGrid,
  AdminTableShell,
} from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import {
  deleteAdminTraining,
  fetchAdminTrainingCategoriesPage,
  fetchAdminTrainingEnrollmentsPage,
  fetchAdminTrainingSessionsPage,
  fetchAdminTrainingsPage,
  type TrainingCategoryDto,
  type TrainingDto,
  type TrainingEnrollmentDto,
  type TrainingSessionDto,
} from '@/features/trainings/publicApi';
import { adminTrainingQueryKeys } from '@/features/admin/trainings/queryKeys';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { formatEuroCents, formatFrenchDateTime, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import type { PaginatedResult } from '@/shared/types/api';

export const AdminTrainingsPage = () => {
  useDocumentTitle('Admin - Formations');
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const [message, setMessage] = useState<string | null>(null);

  const trainingsQuery = useQuery<PaginatedResult<TrainingDto>, Error>({
    queryKey: [...adminTrainingQueryKeys.trainings(), { page }],
    queryFn: () => fetchAdminTrainingsPage(page, 10),
  });
  const categoriesQuery = useQuery<PaginatedResult<TrainingCategoryDto>, Error>({
    queryKey: [...adminTrainingQueryKeys.categories(), { page: 1, perPage: 1 }],
    queryFn: () => fetchAdminTrainingCategoriesPage(1, 1),
  });
  const sessionsQuery = useQuery<PaginatedResult<TrainingSessionDto>, Error>({
    queryKey: [...adminTrainingQueryKeys.sessions(), { page: 1, perPage: 5 }],
    queryFn: () => fetchAdminTrainingSessionsPage(1, 5),
  });
  const enrollmentsQuery = useQuery<PaginatedResult<TrainingEnrollmentDto>, Error>({
    queryKey: [...adminTrainingQueryKeys.enrollments(), { page: 1, perPage: 5 }],
    queryFn: () => fetchAdminTrainingEnrollmentsPage(1, 5),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteAdminTraining,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.trainings() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.sessions() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.enrollments() });
      void queryClient.invalidateQueries({ queryKey: adminTrainingQueryKeys.categories() });
      setMessage(response.message ?? 'La formation a bien été supprimée.');
    },
  });

  const trainings = trainingsQuery.data?.items ?? [];
  const trainingsMeta = trainingsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const categoriesMeta = categoriesQuery.data?.meta ?? { page: 1, perPage: 1, total: 0, totalPages: 1 };
  const sessions = sessionsQuery.data?.items ?? [];
  const sessionsMeta = sessionsQuery.data?.meta ?? { page: 1, perPage: 5, total: 0, totalPages: 1 };
  const enrollments = enrollmentsQuery.data?.items ?? [];
  const enrollmentsMeta = enrollmentsQuery.data?.meta ?? { page: 1, perPage: 5, total: 0, totalPages: 1 };
  const loading =
    trainingsQuery.isLoading || categoriesQuery.isLoading || sessionsQuery.isLoading || enrollmentsQuery.isLoading;
  const error =
    trainingsQuery.error
      ? getHttpErrorMessage(trainingsQuery.error, 'Impossible de charger les formations.')
      : categoriesQuery.error
        ? getHttpErrorMessage(categoriesQuery.error, 'Impossible de charger les catégories.')
        : sessionsQuery.error
          ? getHttpErrorMessage(sessionsQuery.error, 'Impossible de charger les sessions.')
          : enrollmentsQuery.error
            ? getHttpErrorMessage(enrollmentsQuery.error, 'Impossible de charger les inscriptions.')
            : deleteMutation.error
              ? getHttpErrorMessage(deleteMutation.error, 'Impossible de supprimer la formation.')
              : null;

  useEffect(() => {
    const next = new URLSearchParams();
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, setSearchParams]);

  const handleDelete = async (training: TrainingDto) => {
    if (
      !(await confirm({
        title: 'Supprimer la formation',
        description: `Supprimer "${training.title}" ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    ) {
      return;
    }

    setMessage(null);
    deleteMutation.mutate(training.id);
  };

  return (
    <PageContainer
      size="admin"
      title="Formations"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <PrimaryLink to="/admin/trainings/new">Nouvelle formation</PrimaryLink>
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
      <AdminMetricGrid>
        <AdminMetricCard label="Formations" value={trainingsMeta.total} />
        <AdminMetricCard label="Sessions" value={sessionsMeta.total} />
        <AdminMetricCard label="Inscriptions" value={enrollmentsMeta.total} />
        <AdminMetricCard label="Catégories" value={categoriesMeta.total} />
      </AdminMetricGrid>

      <div className="mt-6 mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Cette page affiche le catalogue paginé des formations. Les sessions et inscriptions gardent
          leurs écrans dédiés.
        </p>
        <p className="text-sm text-stone-500">
          Le frontend n’assemble plus un “module complet” en mémoire: chaque bloc dépend d’un endpoint
          backend dédié.
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={trainings.length === 0}
        loadingLabel="Chargement des formations..."
        emptyLabel="Aucune formation enregistrée."
      >
        <section className="space-y-8">
          <div>
            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
              <div>
                <h2 className="text-xl font-semibold text-brand-900">Catalogue des formations</h2>
                <p className="text-sm text-stone-500">Prix, formats, catégorie et publication.</p>
              </div>
              <div className="flex flex-wrap gap-3">
                <Link to="/admin/trainings/categories" className="catalog-admin-actions__edit">
                  Catégories
                </Link>
                <PrimaryLink to="/admin/trainings/new">Nouvelle formation</PrimaryLink>
              </div>
            </div>

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
                        {training.shortDescription ? <p className="muted">{training.shortDescription}</p> : null}
                      </td>
                      <td>{formatEuroCents(training.priceCents)}</td>
                      <td>{training.categoryDetails?.name ?? training.category}</td>
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
            <PaginationControls
              page={trainingsMeta.page}
              total={trainingsMeta.total}
              totalLabel="formation"
              totalPages={trainingsMeta.totalPages}
              onPageChange={setPage}
            />
          </div>

          <div className="grid gap-6 xl:grid-cols-2">
            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <div className="mb-4 flex items-end justify-between gap-3">
                <div>
                  <h2 className="text-lg font-semibold text-brand-900">Sessions récentes</h2>
                  <p className="text-sm text-stone-500">
                    Aperçu backend de la première page des sessions.
                  </p>
                </div>
                <Link to="/admin/trainings/sessions" className="catalog-admin-actions__edit">
                  Voir la liste
                </Link>
              </div>
              {sessions.length === 0 ? (
                <p className="text-sm text-stone-500">Aucune session programmée.</p>
              ) : (
                <div className="space-y-3">
                  {sessions.map((session) => (
                    <article key={session.id} className="rounded-xl border border-brand-100 p-4">
                      <p className="font-semibold text-brand-900">{session.training.title}</p>
                      <p className="text-sm text-stone-600">
                        Du {formatOptionalFrenchDate(session.startsAt)} au {formatOptionalFrenchDate(session.endsAt)}
                      </p>
                      <p className="text-sm text-stone-500">
                        {session.formatLabel}, {session.capacity} participant{session.capacity > 1 ? 's' : ''}
                      </p>
                    </article>
                  ))}
                </div>
              )}
            </section>

            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <div className="mb-4 flex items-end justify-between gap-3">
                <div>
                  <h2 className="text-lg font-semibold text-brand-900">Dernières inscriptions</h2>
                  <p className="text-sm text-stone-500">
                    Aperçu backend des inscriptions les plus récentes.
                  </p>
                </div>
                <Link to="/admin/trainings/enrollments" className="catalog-admin-actions__edit">
                  Voir la liste
                </Link>
              </div>
              {enrollments.length === 0 ? (
                <p className="text-sm text-stone-500">Aucune inscription enregistrée.</p>
              ) : (
                <div className="space-y-3">
                  {enrollments.map((enrollment) => (
                    <article key={enrollment.id} className="rounded-xl border border-brand-100 p-4">
                      <p className="font-semibold text-brand-900">{enrollment.session.training.title}</p>
                      <p className="text-sm text-stone-600">{formatFrenchDateTime(enrollment.scheduledStartsAt)}</p>
                      <p className="text-sm text-stone-500">{enrollment.statusLabel}</p>
                    </article>
                  ))}
                </div>
              )}
            </section>
          </div>
        </section>
      </AdminListState>
    </PageContainer>
  );
};
