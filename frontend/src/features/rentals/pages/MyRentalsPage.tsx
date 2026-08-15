import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageShell, PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { requestRentalChange, fetchMyRentals } from '@/features/rentals/api/rentalsApi';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';
import { useToast } from '@/shared/components/ui/toast';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import type { RentalItemDto } from '../types/rentals';

type RentalFilter = 'all' | 'upcoming' | 'past';

const parseFrenchDateInput = (value: string) => {
  const normalized = value.trim();
  const match = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
  if (!match) {
    return null;
  }

  const [, day, month, year] = match;
  const apiValue = `${year}-${month}-${day}`;
  const parsed = new Date(`${apiValue}T12:00:00`);

  if (Number.isNaN(parsed.getTime())) {
    return null;
  }

  if (formatDateInputForDisplay(apiValue) !== normalized) {
    return null;
  }

  return apiValue;
};

const RentalCard = ({
  rental,
  onRequest,
  loadingAction,
}: {
  rental: RentalItemDto;
  onRequest: (rental: RentalItemDto, action: 'extend' | 'end_early') => void;
  loadingAction?: string | null;
}) => (
  <article className="rounded-3xl border border-brand-100 bg-white p-5 shadow-sm">
    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
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
        {rental.request.status === 'pending' ? (
          <p className="text-sm font-medium text-amber-700">
            Demande en attente: {rental.request.type === 'extend' ? 'prolongation' : 'fin anticipée'}
            {rental.request.requestedEndDate ? ` jusqu’au ${formatDateInputForDisplay(rental.request.requestedEndDate)}` : ''}
          </p>
        ) : null}
      </div>
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="inline-flex rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-900"
          onClick={() => onRequest(rental, 'extend')}
          disabled={loadingAction === `extend:${rental.orderItemId}`}
        >
          {loadingAction === `extend:${rental.orderItemId}` ? 'Envoi...' : 'Demander une prolongation'}
        </button>
        <button
          type="button"
          className="inline-flex rounded-full border border-stone-200 px-4 py-2 text-sm font-semibold text-stone-700"
          onClick={() => onRequest(rental, 'end_early')}
          disabled={loadingAction === `end_early:${rental.orderItemId}`}
        >
          {loadingAction === `end_early:${rental.orderItemId}` ? 'Envoi...' : 'Anticiper la fin'}
        </button>
      </div>
    </div>
  </article>
);

export const MyRentalsPage = () => {
  useDocumentTitle('Mes locations');
  const toast = useToast();
  const queryClient = useQueryClient();
  const [loadingAction, setLoadingAction] = useState<string | null>(null);
  const [activeFilter, setActiveFilter] = useState<RentalFilter>('all');

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
    }) => requestRentalChange(orderItemId, { action, requestedEndDate }),
    onSuccess: async (updatedRental, variables) => {
      await queryClient.invalidateQueries({ queryKey: ['rentals', 'me'] });
      toast.show(
        variables.action === 'extend' && updatedRental.request.status !== 'pending'
          ? `La location est prolongée jusqu’au ${formatDateInputForDisplay(updatedRental.endDate)}.`
          : variables.action === 'extend'
            ? 'Votre demande de prolongation a bien été enregistrée.'
            : 'Votre demande de fin anticipée a bien été enregistrée.',
        { variant: 'success' },
      );
    },
    onError: (reason) => {
      toast.show(getHttpErrorMessage(reason, "La demande de location n'a pas pu être traitée."), { variant: 'error' });
    },
    onSettled: () => setLoadingAction(null),
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

  const askForDate = (rental: RentalItemDto, action: 'extend' | 'end_early') => {
    const defaultDate = rental.endDate ?? rental.startDate ?? '';
    const defaultDisplayDate = formatDateInputForDisplay(defaultDate);
    const value = window.prompt(
      action === 'extend'
        ? 'Nouvelle date de fin souhaitée (jj/mm/AAAA)'
        : 'Date de fin anticipée souhaitée (jj/mm/AAAA)',
      defaultDisplayDate !== '-' ? defaultDisplayDate : '',
    );

    if (!value) {
      return;
    }

    const requestedEndDate = parseFrenchDateInput(value);
    if (!requestedEndDate) {
      toast.show('Saisissez une date au format jj/mm/AAAA.', { variant: 'error' });
      return;
    }

    setLoadingAction(`${action}:${rental.orderItemId}`);
    requestMutation.mutate({
      orderItemId: rental.orderItemId,
      action,
      requestedEndDate,
    });
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        title="Mes locations"
        description="Suivez vos locations à venir, en cours ou terminées, et transmettez vos demandes d’ajustement de période."
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
                <div
                  className="flex flex-wrap gap-2"
                  role="tablist"
                  aria-label="Filtrer mes locations"
                >
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
                          isSelected
                            ? 'border-brand-900 bg-brand-900 text-white'
                            : 'border-brand-200 text-brand-900',
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
                      onRequest={askForDate}
                      loadingAction={loadingAction}
                    />
                  ))
                )}
              </div>
            </PublicPageSection>
          </div>
        ) : null}
      </PublicPageShell>
    </SiteLayout>
  );
};
