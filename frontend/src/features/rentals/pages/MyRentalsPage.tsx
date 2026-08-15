import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageShell, PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { fetchMyRentals, requestRentalChange, terminateRental } from '@/features/rentals/api/rentalsApi';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatApiDateForDateInput, formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import { Dialog, DialogBackdrop, DialogDescription, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import type { RentalItemDto } from '../types/rentals';

type RentalFilter = 'all' | 'upcoming' | 'past';

type RentalDialogState =
  | { type: 'extend'; rental: RentalItemDto }
  | { type: 'terminate'; rental: RentalItemDto };

type RentalExtensionOption = {
  totalMonths: number;
  additionalMonths: number;
  endDate: string;
};

const formatReturnMode = (mode: string | null | undefined) =>
  mode === 'pickup_home' ? 'Récupération à domicile' : mode === 'dropoff_store' ? 'Dépôt en boutique' : 'Non défini';

const parseApiDate = (value: string | null | undefined) => {
  if (!value) return null;
  const date = new Date(`${value}T00:00:00`);
  return Number.isNaN(date.getTime()) ? null : date;
};

const computeAlignedRentalEndDate = (startDate: string, totalMonths: number) => {
  const start = parseApiDate(startDate);
  if (!start) {
    return startDate;
  }

  start.setMonth(start.getMonth() + Math.max(1, totalMonths));
  start.setDate(start.getDate() - 1);

  return formatApiDateForDateInput(start);
};

const buildExtensionOptions = (rental: RentalItemDto, limit = 6): RentalExtensionOption[] => {
  if (!rental.startDate) {
    return [];
  }

  const currentMonths = Math.max(1, rental.rentalMonths ?? 1);

  return Array.from({ length: limit }, (_, index) => {
    const additionalMonths = index + 1;
    const totalMonths = currentMonths + additionalMonths;

    return {
      totalMonths,
      additionalMonths,
      endDate: computeAlignedRentalEndDate(rental.startDate ?? '', totalMonths),
    };
  });
};

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
  const hasPendingExtensionPayment = rental.request.status === 'pending_payment';

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
                : `Demande en attente: ${rental.request.type === 'extend' ? 'prolongation' : 'fin de location'}${rental.request.requestedEndDate ? ` jusqu’au ${formatDateInputForDisplay(rental.request.requestedEndDate)}` : ''}`}
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
              disabled={loadingAction === `extend:${rental.orderItemId}` || hasPendingExtensionPayment}
            >
              {loadingAction === `extend:${rental.orderItemId}` ? 'Préparation...' : 'Prolonger'}
            </button>
            <button
              type="button"
              className="inline-flex rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white"
              onClick={() => onOpenDialog({ type: 'terminate', rental })}
              disabled={loadingAction === `terminate:${rental.orderItemId}`}
            >
              {loadingAction === `terminate:${rental.orderItemId}` ? 'Envoi...' : 'Terminer la location'}
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
  onConfirmExtension,
  onConfirmTermination,
}: {
  state: RentalDialogState | null;
  submitting: boolean;
  onClose: () => void;
  onConfirmExtension: (rental: RentalItemDto, requestedEndDate: string) => void;
  onConfirmTermination: (
    rental: RentalItemDto,
    requestedEndDate: string,
    returnMode: 'pickup_home' | 'dropoff_store',
    returnRequestedDate: string,
  ) => void;
}) => {
  const [requestedEndDate, setRequestedEndDate] = useState<string>('');
  const [requestedReturnDate, setRequestedReturnDate] = useState<string>('');
  const [returnMode, setReturnMode] = useState<'pickup_home' | 'dropoff_store'>('pickup_home');

  if (!state) {
    return null;
  }

  const rental = state.rental;
  const extensionOptions = state.type === 'extend' ? buildExtensionOptions(rental) : [];
  const title = state.type === 'extend' ? 'Prolonger la location' : 'Terminer la location';

  const defaultTerminationDate = rental.endDate ?? '';
  const normalizedTerminationDate = requestedEndDate || defaultTerminationDate;
  const normalizedReturnDate = requestedReturnDate || normalizedTerminationDate;
  const terminationMaxDate = normalizedTerminationDate || rental.endDate || undefined;

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

              {state.type === 'terminate' ? (
                <>
                  <label className="grid gap-2 text-sm font-medium text-brand-950">
                    Date de fin souhaitée
                    <input
                      type="date"
                      className="rounded-2xl border border-brand-200 px-4 py-3 text-sm"
                      value={normalizedTerminationDate}
                      min={rental.startDate ?? undefined}
                      max={rental.endDate ?? undefined}
                      onChange={(event) => {
                        const value = event.target.value;
                        setRequestedEndDate(value);
                        if (requestedReturnDate && requestedReturnDate > value) {
                          setRequestedReturnDate(value);
                        }
                      }}
                    />
                  </label>
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
                      value={normalizedReturnDate}
                      min={rental.startDate ?? undefined}
                      max={terminationMaxDate}
                      onChange={(event) => setRequestedReturnDate(event.target.value)}
                    />
                  </label>
                </>
              ) : (
                <div className="grid gap-3">
                  <p className="text-sm text-stone-600">
                    Choisissez une échéance valide. La prolongation sera ensuite envoyée au paiement.
                  </p>
                  {extensionOptions.map((option) => {
                    const isSelected = requestedEndDate === option.endDate || (!requestedEndDate && option === extensionOptions[0]);

                    return (
                      <button
                        key={option.endDate}
                        type="button"
                        className={[
                          'flex items-center justify-between rounded-2xl border px-4 py-3 text-left transition',
                          isSelected ? 'border-brand-900 bg-brand-50' : 'border-brand-100 bg-white',
                        ].join(' ')}
                        onClick={() => setRequestedEndDate(option.endDate)}
                      >
                        <span>
                          <span className="block text-sm font-semibold text-brand-950">+{option.additionalMonths} mois</span>
                          <span className="block text-sm text-stone-600">Jusqu’au {formatDateInputForDisplay(option.endDate)}</span>
                        </span>
                        <span className="text-sm font-semibold text-brand-900">{option.totalMonths} mois au total</span>
                      </button>
                    );
                  })}
                </div>
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
                  if (state.type === 'terminate') {
                    onConfirmTermination(rental, normalizedTerminationDate, returnMode, normalizedReturnDate);
                    return;
                  }
                  onConfirmExtension(rental, requestedEndDate || extensionOptions[0]?.endDate || '');
                }}
              >
                {submitting ? 'Envoi...' : state.type === 'extend' ? 'Continuer vers le paiement' : 'Valider'}
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
      requestedEndDate,
    }: {
      orderItemId: number;
      requestedEndDate: string;
    }) => requestRentalChange(orderItemId, { action: 'extend', requestedEndDate, clientPlatform: 'web' }),
    onSuccess: async ({ rental, checkout }) => {
      await queryClient.invalidateQueries({ queryKey: ['rentals', 'me'] });
      if (checkout?.checkoutUrl) {
        redirectToTrustedUrl(checkout.checkoutUrl);
        return;
      }

      toast.show(`La location est prolongée jusqu’au ${formatDateInputForDisplay(rental.endDate)}.`, {
        variant: 'success',
      });
    },
    onError: (reason) => {
      toast.show(getHttpErrorMessage(reason, "La prolongation n'a pas pu être préparée."), { variant: 'error' });
    },
    onSettled: () => {
      setLoadingAction(null);
      setDialogState(null);
    },
  });

  const terminationMutation = useMutation({
    mutationFn: ({
      orderItemId,
      requestedEndDate,
      returnMode,
      returnRequestedDate,
    }: {
      orderItemId: number;
      requestedEndDate: string;
      returnMode: 'pickup_home' | 'dropoff_store';
      returnRequestedDate: string;
    }) => terminateRental(orderItemId, { requestedEndDate, returnMode, returnRequestedDate }),
    onSuccess: async (rental) => {
      await queryClient.invalidateQueries({ queryKey: ['rentals', 'me'] });
      toast.show(
        rental.request.type === 'end_early'
          ? 'Votre fin de location et la restitution ont bien été enregistrées.'
          : `${formatReturnMode(rental.returnPlan.mode)} planifié${rental.returnPlan.requestedDate ? ` pour le ${formatDateInputForDisplay(rental.returnPlan.requestedDate)}` : '.'}`,
        { variant: 'success' },
      );
    },
    onError: (reason) => {
      toast.show(getHttpErrorMessage(reason, "La fin de location n'a pas pu être enregistrée."), { variant: 'error' });
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

  const submitExtension = (rental: RentalItemDto, requestedEndDate: string) => {
    if (!requestedEndDate) {
      toast.show('Choisissez une échéance valide.', { variant: 'error' });
      return;
    }

    setLoadingAction(`extend:${rental.orderItemId}`);
    requestMutation.mutate({
      orderItemId: rental.orderItemId,
      requestedEndDate,
    });
  };

  const submitTermination = (
    rental: RentalItemDto,
    requestedEndDate: string,
    returnMode: 'pickup_home' | 'dropoff_store',
    returnRequestedDate: string,
  ) => {
    if (!requestedEndDate || !returnRequestedDate) {
      toast.show('Choisissez des dates valides.', { variant: 'error' });
      return;
    }

    setLoadingAction(`terminate:${rental.orderItemId}`);
    terminationMutation.mutate({
      orderItemId: rental.orderItemId,
      requestedEndDate,
      returnMode,
      returnRequestedDate,
    });
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        title="Mes locations"
        description="Suivez vos locations, payez vos prolongations et terminez une location en planifiant le retour du matériel."
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
        submitting={requestMutation.isPending || terminationMutation.isPending}
        onClose={() => setDialogState(null)}
        onConfirmExtension={submitExtension}
        onConfirmTermination={submitTermination}
      />
    </SiteLayout>
  );
};
