import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageShell, PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { fetchMyRentals, planRentalReturn, requestRentalChange } from '@/features/rentals/api/rentalsApi';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import { Dialog, DialogBackdrop, DialogDescription, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import type { RentalItemDto } from '../types/rentals';

type RentalFilter = 'all' | 'upcoming' | 'past';

type RentalDialogState =
  | { type: 'extend' | 'end_early'; rental: RentalItemDto }
  | { type: 'return'; rental: RentalItemDto };

const formatReturnMode = (mode: string | null | undefined) =>
  mode === 'pickup_home' ? 'Récupération à domicile' : mode === 'dropoff_store' ? 'Dépôt en boutique' : 'Non défini';

const RentalCard = ({
  rental,
  onOpenDialog,
  loadingAction,
}: {
  rental: RentalItemDto;
  onOpenDialog: (state: RentalDialogState) => void;
  loadingAction?: string | null;
}) => {
  const isReturned = rental.returnPlan.status === 'completed';

  return (
    <article className="rounded-3xl border border-brand-100 bg-white p-5 shadow-sm">
      <div className="flex flex-col gap-4">
        <div className="space-y-2">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
            {rental.timelineStatusLabel}
          </p>
          <h2 className="text-xl font-semibold text-brand-950">{rental.productName}</h2>
          <p className="text-sm text-stone-600">Commande {rental.orderNumber ?? '-'}</p>
          <p className="text-sm text-stone-700">
            Du {formatDateInputForDisplay(rental.startDate)} au {formatDateInputForDisplay(rental.endDate)}
          </p>
          <p className="text-sm text-stone-700">
            {rental.rentalMonths ?? 0} mois · {formatEuroCents(rental.linePriceCents)}
          </p>
          {rental.request.status !== 'none' && !isReturned ? (
            <p className="text-sm font-medium text-amber-700">
              {rental.request.status === 'pending_payment'
                ? `Paiement de prolongation en attente jusqu’au ${formatDateInputForDisplay(rental.request.requestedEndDate)}`
                : `Demande en attente: ${rental.request.type === 'extend' ? 'prolongation' : 'fin anticipée'}${rental.request.requestedEndDate ? ` jusqu’au ${formatDateInputForDisplay(rental.request.requestedEndDate)}` : ''}`}
            </p>
          ) : null}
          {rental.returnPlan.status !== 'none' ? (
            <p className="text-sm font-medium text-brand-800">
              {isReturned
                ? 'Matériel restitué. Cette location est clôturée.'
                : `${formatReturnMode(rental.returnPlan.mode)} prévu le ${formatDateInputForDisplay(rental.returnPlan.requestedDate)}`}
            </p>
          ) : null}
        </div>
        {!isReturned ? (
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              className="inline-flex rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-900"
              onClick={() => onOpenDialog({ type: 'extend', rental })}
              disabled={loadingAction === `extend:${rental.orderItemId}`}
            >
              {loadingAction === `extend:${rental.orderItemId}` ? 'Préparation...' : 'Prolonger'}
            </button>
            <button
              type="button"
              className="inline-flex rounded-full border border-stone-200 px-4 py-2 text-sm font-semibold text-stone-700"
              onClick={() => onOpenDialog({ type: 'end_early', rental })}
              disabled={loadingAction === `end_early:${rental.orderItemId}`}
            >
              {loadingAction === `end_early:${rental.orderItemId}` ? 'Envoi...' : 'Anticiper la fin'}
            </button>
            <button
              type="button"
              className="inline-flex rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-900"
              onClick={() => onOpenDialog({ type: 'return', rental })}
              disabled={loadingAction === `return:${rental.orderItemId}`}
            >
              {loadingAction === `return:${rental.orderItemId}` ? 'Envoi...' : 'Organiser la restitution'}
            </button>
          </div>
        ) : null}
      </div>
    </article>
  );
};

const RentalActionDialog = ({
  state,
  submitting,
  onClose,
  onConfirmChange,
  onConfirmReturn,
}: {
  state: RentalDialogState | null;
  submitting: boolean;
  onClose: () => void;
  onConfirmChange: (action: 'extend' | 'end_early', rental: RentalItemDto, requestedEndDate: string) => void;
  onConfirmReturn: (rental: RentalItemDto, mode: 'pickup_home' | 'dropoff_store', requestedDate: string) => void;
}) => {
  const [requestedEndDate, setRequestedEndDate] = useState('');
  const [requestedReturnDate, setRequestedReturnDate] = useState('');
  const [returnMode, setReturnMode] = useState<'pickup_home' | 'dropoff_store'>('pickup_home');

  if (!state) {
    return null;
  }

  const rental = state.rental;
  const title = state.type === 'extend' ? 'Prolonger la location' : state.type === 'end_early' ? 'Demander une fin anticipée' : 'Organiser la restitution';

  return (
    <Dialog open onClose={submitting ? () => undefined : onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-950/60" />
      <div className="fixed inset-0 overflow-y-auto">
        <div className="flex min-h-full items-center justify-center p-4">
          <DialogPanel className="w-full max-w-xl rounded-3xl border border-brand-100 bg-white p-6 shadow-2xl">
            <div className="space-y-2">
              <DialogTitle className="text-2xl font-semibold text-brand-950">{title}</DialogTitle>
              <DialogDescription className="text-sm text-stone-600">
                {rental.productName}
              </DialogDescription>
            </div>

            <div className="mt-6 grid gap-4">
              <p className="text-sm text-stone-700">
                Période actuelle: {formatDateInputForDisplay(rental.startDate)} au {formatDateInputForDisplay(rental.endDate)}
              </p>

              {state.type === 'return' ? (
                <>
                  <label className="grid gap-2 text-sm font-medium text-brand-950">
                    Mode de restitution
                    <select
                      className="rounded-2xl border border-brand-200 px-4 py-3 text-sm"
                      value={returnMode}
                      onChange={(event) => setReturnMode(event.target.value as 'pickup_home' | 'dropoff_store')}
                    >
                      <option value="pickup_home">Récupération à domicile</option>
                      <option value="dropoff_store">Dépôt en boutique</option>
                    </select>
                  </label>
                  <label className="grid gap-2 text-sm font-medium text-brand-950">
                    Date souhaitée
                    <input
                      type="date"
                      className="rounded-2xl border border-brand-200 px-4 py-3 text-sm"
                      value={requestedReturnDate}
                      min={rental.startDate ?? undefined}
                      max={rental.endDate ?? undefined}
                      onChange={(event) => setRequestedReturnDate(event.target.value)}
                    />
                  </label>
                </>
              ) : (
                <label className="grid gap-2 text-sm font-medium text-brand-950">
                  Nouvelle date de fin
                  <input
                    type="date"
                    className="rounded-2xl border border-brand-200 px-4 py-3 text-sm"
                    value={requestedEndDate}
                    min={rental.startDate ?? undefined}
                    onChange={(event) => setRequestedEndDate(event.target.value)}
                  />
                </label>
              )}
            </div>

            <div className="mt-6 flex flex-wrap justify-end gap-3">
              <button
                type="button"
                className="rounded-full border border-stone-200 px-4 py-2 text-sm font-semibold text-stone-700"
                onClick={onClose}
                disabled={submitting}
              >
                Annuler
              </button>
              <button
                type="button"
                className="rounded-full bg-brand-900 px-5 py-2 text-sm font-semibold text-white"
                disabled={submitting}
                onClick={() => {
                  if (state.type === 'return') {
                    onConfirmReturn(rental, returnMode, requestedReturnDate);
                    return;
                  }
                  onConfirmChange(state.type, rental, requestedEndDate);
                }}
              >
                {submitting ? 'Envoi...' : state.type === 'extend' ? 'Payer la prolongation' : state.type === 'end_early' ? 'Envoyer la demande' : 'Planifier'}
              </button>
            </div>
          </DialogPanel>
        </div>
      </div>
    </Dialog>
  );
};

export const MyRentalsPage = () => {
  useDocumentTitle('Mes locations');
  const toast = useToast();
  const queryClient = useQueryClient();
  const [loadingAction, setLoadingAction] = useState<string | null>(null);
  const [activeFilter, setActiveFilter] = useState<RentalFilter>('all');
  const [dialogState, setDialogState] = useState<RentalDialogState | null>(null);

  const rentalsQuery = useQuery({
    queryKey: ['rentals', 'me'],
    queryFn: fetchMyRentals,
  });

  const requestMutation = useMutation({
    mutationFn: ({
      orderItemId,
      action,
      requestedEndDate,
    }: {
      orderItemId: number;
      action: 'extend' | 'end_early';
      requestedEndDate: string;
    }) => requestRentalChange(orderItemId, { action, requestedEndDate, clientPlatform: 'web' }),
    onSuccess: async ({ rental, checkout }, variables) => {
      await queryClient.invalidateQueries({ queryKey: ['rentals', 'me'] });
      if (checkout?.checkoutUrl) {
        redirectToTrustedUrl(checkout.checkoutUrl);
        return;
      }

      toast.show(
        variables.action === 'extend'
          ? `La location est prolongée jusqu’au ${formatDateInputForDisplay(rental.endDate)}.`
          : 'Votre demande de fin anticipée a bien été enregistrée.',
        { variant: 'success' },
      );
    },
    onError: (reason) => {
      toast.show(getHttpErrorMessage(reason, "La demande de location n'a pas pu être traitée."), { variant: 'error' });
    },
    onSettled: () => {
      setLoadingAction(null);
      setDialogState(null);
    },
  });

  const returnMutation = useMutation({
    mutationFn: ({
      orderItemId,
      mode,
      requestedDate,
    }: {
      orderItemId: number;
      mode: 'pickup_home' | 'dropoff_store';
      requestedDate: string;
    }) => planRentalReturn(orderItemId, { mode, requestedDate }),
    onSuccess: async (rental) => {
      await queryClient.invalidateQueries({ queryKey: ['rentals', 'me'] });
      toast.show(
        `${formatReturnMode(rental.returnPlan.mode)} planifié${rental.returnPlan.requestedDate ? ` pour le ${formatDateInputForDisplay(rental.returnPlan.requestedDate)}` : '.'}`,
        { variant: 'success' },
      );
    },
    onError: (reason) => {
      toast.show(getHttpErrorMessage(reason, "La restitution n'a pas pu être planifiée."), { variant: 'error' });
    },
    onSettled: () => {
      setLoadingAction(null);
      setDialogState(null);
    },
  });

  const upcoming = rentalsQuery.data?.upcoming ?? [];
  const past = rentalsQuery.data?.past ?? [];
  const total = useMemo(() => upcoming.length + past.length, [past.length, upcoming.length]);
  const filteredRentals = useMemo(() => {
    switch (activeFilter) {
      case 'upcoming':
        return upcoming;
      case 'past':
        return past;
      default:
        return [...upcoming, ...past];
    }
  }, [activeFilter, past, upcoming]);

  const submitChange = (action: 'extend' | 'end_early', rental: RentalItemDto, requestedEndDate: string) => {
    if (!requestedEndDate) {
      toast.show('Choisissez une date valide.', { variant: 'error' });
      return;
    }

    setLoadingAction(`${action}:${rental.orderItemId}`);
    requestMutation.mutate({
      orderItemId: rental.orderItemId,
      action,
      requestedEndDate,
    });
  };

  const submitReturn = (rental: RentalItemDto, mode: 'pickup_home' | 'dropoff_store', requestedDate: string) => {
    if (!requestedDate) {
      toast.show('Choisissez une date valide.', { variant: 'error' });
      return;
    }

    setLoadingAction(`return:${rental.orderItemId}`);
    returnMutation.mutate({
      orderItemId: rental.orderItemId,
      mode,
      requestedDate,
    });
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        title="Mes locations"
        description="Suivez vos locations, payez vos prolongations et organisez la restitution du matériel."
      >
        {rentalsQuery.isLoading ? <LoadingState>Chargement des locations...</LoadingState> : null}
        {rentalsQuery.isError ? (
          <ErrorState onAction={() => void rentalsQuery.refetch()}>
            Impossible de charger vos locations.
          </ErrorState>
        ) : null}

        {!rentalsQuery.isLoading && !rentalsQuery.isError ? (
          <div className="grid gap-6">
            <section className="grid gap-3 sm:grid-cols-3">
              <div className="rounded-3xl border border-brand-100 bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">À venir / en cours</p>
                <p className="mt-2 text-3xl font-semibold text-brand-950">{upcoming.length}</p>
              </div>
              <div className="rounded-3xl border border-brand-100 bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Terminées</p>
                <p className="mt-2 text-3xl font-semibold text-brand-950">{past.length}</p>
              </div>
              <div className="rounded-3xl border border-brand-100 bg-white p-5 shadow-sm">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Total</p>
                <p className="mt-2 text-3xl font-semibold text-brand-950">{total}</p>
              </div>
            </section>

            <PublicPageSection>
              <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 className="text-2xl font-semibold text-brand-950">Mes locations</h2>
                <div className="flex flex-wrap gap-2" role="tablist" aria-label="Filtrer mes locations">
                  {[
                    { key: 'all' as const, label: `Toutes (${total})` },
                    { key: 'upcoming' as const, label: `À venir (${upcoming.length})` },
                    { key: 'past' as const, label: `Passées (${past.length})` },
                  ].map((filter) => {
                    const isSelected = activeFilter === filter.key;
                    return (
                      <button
                        key={filter.key}
                        type="button"
                        role="tab"
                        aria-selected={isSelected}
                        className={[
                          'inline-flex rounded-full border px-4 py-2 text-sm font-semibold transition',
                          isSelected ? 'border-brand-900 bg-brand-900 text-white' : 'border-brand-200 text-brand-900',
                        ].join(' ')}
                        onClick={() => setActiveFilter(filter.key)}
                      >
                        {filter.label}
                      </button>
                    );
                  })}
                </div>
              </div>
              <div className="mt-4 grid gap-4">
                {filteredRentals.length === 0 ? (
                  <p className="text-sm text-stone-600">
                    {activeFilter === 'upcoming'
                      ? 'Aucune location à venir ou en cours.'
                      : activeFilter === 'past'
                        ? 'Aucune location terminée.'
                        : 'Aucune location disponible.'}
                  </p>
                ) : (
                  filteredRentals.map((rental) => (
                    <RentalCard
                      key={`${activeFilter}-${rental.orderItemId}`}
                      rental={rental}
                      onOpenDialog={setDialogState}
                      loadingAction={loadingAction}
                    />
                  ))
                )}
              </div>
            </PublicPageSection>
          </div>
        ) : null}
      </PublicPageShell>
      <RentalActionDialog
        state={dialogState}
        submitting={requestMutation.isPending || returnMutation.isPending}
        onClose={() => setDialogState(null)}
        onConfirmChange={submitChange}
        onConfirmReturn={submitReturn}
      />
    </SiteLayout>
  );
};
