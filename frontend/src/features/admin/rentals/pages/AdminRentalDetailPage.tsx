import { useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { fetchAdminRental, updateAdminRental, type AdminRentalAction } from '../api';
import { adminRentalQueryKeys } from '../queryKeys';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState, NotFoundState } from '@/shared/components/ui/page-state';
import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const actionLabels: Record<AdminRentalAction, string> = {
  approve_extension: 'Approuver la prolongation',
  approve_end_early: 'Approuver la fin anticipée',
  reject_request: 'Rejeter la demande',
};

const requestTypeLabel = (value: string | null | undefined) => {
  if (value === 'extend') {
    return 'Prolongation';
  }

  if (value === 'end_early') {
    return 'Fin anticipée';
  }

  return 'Aucune';
};

export const AdminRentalDetailPage = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { rentalId } = useParams();
  const parsedRentalId = parseNullablePositiveInteger(rentalId);
  const [message, setMessage] = useState<string | null>(null);

  useDocumentTitle(parsedRentalId ? `Admin - Location ${parsedRentalId}` : 'Admin - Location');

  const rentalQuery = useQuery({
    queryKey: adminRentalQueryKeys.detail(parsedRentalId),
    queryFn: () => fetchAdminRental(parsedRentalId as number),
    enabled: parsedRentalId !== null,
  });

  const actionMutation = useMutation({
    mutationFn: (action: AdminRentalAction) => updateAdminRental(parsedRentalId as number, action),
    onSuccess: async (result) => {
      setMessage(result.message ?? 'La location a bien été mise à jour.');
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: adminRentalQueryKeys.detail(parsedRentalId) }),
        queryClient.invalidateQueries({ queryKey: ['admin', 'rentals'] }),
      ]);
    },
    onError: (error) => {
      setMessage(getHttpErrorMessage(error, 'Impossible de mettre à jour la location.'));
    },
  });

  if (parsedRentalId === null) {
    return <NotFoundState>Identifiant de location invalide.</NotFoundState>;
  }

  const rental = rentalQuery.data;
  const loadError = rentalQuery.error ? getHttpErrorMessage(rentalQuery.error, 'Impossible de charger la location.') : null;
  const actionError = actionMutation.isError && message ? message : null;
  const actionSuccess = actionMutation.isSuccess && message ? message : null;

  return (
    <PageContainer
      size="admin"
      title={rental ? `Location ${rental.orderNumber ?? `#${rental.orderItemId}`}` : 'Location'}
      headerActions={
        <button
          type="button"
          className="underline text-sm"
          onClick={() => navigate('/admin/rentals')}
        >
          Retour aux locations
        </button>
      }
    >
      {rentalQuery.isLoading && <LoadingState>Chargement...</LoadingState>}
      {loadError && <FeedbackMessage>{loadError}</FeedbackMessage>}
      {actionError && <FeedbackMessage>{actionError}</FeedbackMessage>}
      {actionSuccess && <FeedbackMessage variant="success">{actionSuccess}</FeedbackMessage>}

      {!rentalQuery.isLoading && !loadError && !rental ? (
        <NotFoundState>Cette location n’existe pas ou n’est plus accessible.</NotFoundState>
      ) : null}

      {rental ? (
        <div className="space-y-6">
          <section className="grid gap-4 rounded-3xl border border-stone-200 bg-white p-6 md:grid-cols-2 xl:grid-cols-4">
            <div>
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Produit</div>
              <div className="mt-2 font-semibold text-stone-900">{rental.productName}</div>
              <div className="text-sm text-stone-600">{rental.productSku}</div>
            </div>
            <div>
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Client</div>
              <div className="mt-2 font-semibold text-stone-900">
                {[rental.customer.firstName, rental.customer.lastName].filter(Boolean).join(' ') || 'Client non renseigné'}
              </div>
              <div className="text-sm text-stone-600">{rental.customer.email ?? '-'}</div>
            </div>
            <div>
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Commande</div>
              <div className="mt-2 font-semibold text-stone-900">{rental.orderNumber ?? '-'}</div>
              <div className="text-sm text-stone-600">{rental.orderStatusLabel ?? rental.orderStatus ?? '-'}</div>
            </div>
            <div>
              <div className="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Montant</div>
              <div className="mt-2 font-semibold text-stone-900">{formatEuroCents(rental.linePriceCents)}</div>
              <div className="text-sm text-stone-600">{rental.quantity} unité{rental.quantity > 1 ? 's' : ''}</div>
            </div>
          </section>

          <section className="grid gap-4 rounded-3xl border border-stone-200 bg-white p-6 md:grid-cols-2">
            <div className="space-y-2">
              <h2 className="text-lg font-semibold text-stone-900">Période de location</h2>
              <div>Début: {formatDateInputForDisplay(rental.startDate)}</div>
              <div>Fin: {formatDateInputForDisplay(rental.endDate)}</div>
              <div>Durée: {rental.rentalMonths ? `${rental.rentalMonths} mois` : '-'}</div>
              <div>Statut: {rental.timelineStatusLabel}</div>
            </div>
            <div className="space-y-2">
              <h2 className="text-lg font-semibold text-stone-900">Demande client</h2>
              <div>État: {rental.request.status === 'pending' ? 'En attente' : 'Aucune demande en attente'}</div>
              <div>Type: {requestTypeLabel(rental.request.type)}</div>
              <div>Date demandée: {formatDateInputForDisplay(rental.request.requestedEndDate)}</div>
            </div>
          </section>

          <section className="space-y-3 rounded-3xl border border-stone-200 bg-white p-6">
            <h2 className="text-lg font-semibold text-stone-900">Actions de gestion</h2>
            {rental.allowedAdminActions.length === 0 ? (
              <p className="text-sm text-stone-600">Aucune action en attente sur cette location.</p>
            ) : (
              <div className="flex flex-wrap gap-3">
                {rental.allowedAdminActions.map((action) => (
                  <button
                    key={action}
                    type="button"
                    className="rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-800 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={actionMutation.isPending}
                    onClick={() => {
                      setMessage(null);
                      actionMutation.mutate(action);
                    }}
                  >
                    {actionLabels[action]}
                  </button>
                ))}
              </div>
            )}
          </section>
        </div>
      ) : null}
    </PageContainer>
  );
};
