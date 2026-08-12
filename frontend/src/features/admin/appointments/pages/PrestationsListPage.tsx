import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router';

import { deletePrestation, fetchAdminPrestationsPage } from '@/features/admin/appointments/api';
import type { Prestation } from '@/features/appointments/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents } from '@/shared/lib/formatters';
import { adminAppointmentQueryKeys } from '@/features/admin/appointments/queryKeys';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginatedResult } from '@/shared/types/api';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const PrestationsListPage = () => {
  useDocumentTitle('Admin - Motifs de rendez-vous');

  const [message, setMessage] = useState<string | null>(null);
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const prestationsQuery = useQuery<PaginatedResult<Prestation>, Error>({
    queryKey: [...adminAppointmentQueryKeys.prestations(), { page }],
    queryFn: () => fetchAdminPrestationsPage(page, 10),
  });
  const deleteMutation = useMutation({
    mutationFn: deletePrestation,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminAppointmentQueryKeys.prestations() });
      setMessage(response.message ?? 'La prestation a bien été supprimée.');
    },
  });
  const prestations = prestationsQuery.data?.items ?? [];
  const prestationsMeta = prestationsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const error = prestationsQuery.error
    ? getHttpErrorMessage(prestationsQuery.error, 'Erreur lors du chargement des prestations')
    : deleteMutation.error
      ? getHttpErrorMessage(deleteMutation.error, 'Impossible de supprimer la prestation')
      : null;

  useEffect(() => {
    const next = new URLSearchParams();
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, setSearchParams]);

  const handleDelete = async (prestationId: number) => {
    const prestation = prestations.find((item) => item.id === prestationId);
    const prestationLabel = prestation ? `"${prestation.name}"` : 'cette prestation';

    const confirmed = await confirm({
      title: 'Supprimer la prestation',
      description: `Supprimer ${prestationLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setMessage(null);
    deleteMutation.mutate(prestationId);
  };

  return (
    <PageContainer
      size="admin"
      title="Motifs de rendez-vous"
      headerActions={
        <PrimaryLink to="/admin/appointments/motifs/new">Ajouter un motif</PrimaryLink>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {prestationsMeta.total} motif{prestationsMeta.total > 1 ? 's' : ''} au catalogue.
        </p>
        <p className="text-sm text-stone-500">
          Ces motifs sont utilisés uniquement pour la prise de rendez-vous et la planification des
          interventions.
        </p>
        <p className="text-sm text-brand-700">
          Pour le catalogue de services global, utilisez{' '}
          <Link to="/admin/services" className="font-semibold underline">
            Services
          </Link>
          .
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={prestationsQuery.isLoading}
        isEmpty={prestations.length === 0}
        loadingLabel="Chargement des motifs..."
        emptyLabel="Aucun motif enregistré."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Durée</th>
                <th scope="col">Prix</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {prestations.map((prestation) => (
                <tr key={prestation.id}>
                  <th scope="row">{prestation.name}</th>
                  <td>{prestation.durationMinutes} min</td>
                  <td>{formatEuroCents(prestation.priceCents)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/appointments/motifs/${prestation.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier la prestation ${prestation.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(prestation.id)}
                        aria-label={`Supprimer la prestation ${prestation.name}`}
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
          page={prestationsMeta.page}
          total={prestationsMeta.total}
          totalLabel="motif"
          totalPages={prestationsMeta.totalPages}
          onPageChange={setPage}
        />
      </AdminListState>
    </PageContainer>
  );
};
