import { useState } from 'react';
import { PageContainer } from '../../../shared/components/PageContainer';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import type { AppointmentItem } from '../types/appointments';
import { useMyAppointments } from '../hooks/useMyAppointments';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, StableContent } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

const PAST_APPOINTMENTS_PER_PAGE = 5;

export const MyAppointmentsPage = () => {
  useDocumentTitle('Mes rendez-vous');

  const [pastPage, setPastPage] = useState(1);
  const { loading, error, upcoming, past, cancellingId, cancel } = useMyAppointments();
  const confirm = useConfirm();

  const handleCancel = async (id: number) => {
    const confirmed = await confirm({
      title: 'Annuler le rendez-vous',
      description: 'Êtes-vous sûr de vouloir annuler ce rendez-vous ?',
      confirmLabel: 'Annuler le rendez-vous',
      cancelLabel: 'Conserver',
    });

    if (!confirmed) {
      return;
    }

    await cancel(id);
  };

  const renderList = (items: AppointmentItem[], showCancelButton = false) => (
    <ul className="grid list-none gap-3 p-0">
      {items.map((appointment) => {
        const isCancelled = appointment.status === 'Annulé';
        const canCancel = showCancelButton && !isCancelled;

        return (
          <li key={appointment.id} className="rounded-lg border border-brand-200 p-3">
            <div className="flex flex-wrap justify-between gap-3">
              <strong>{appointment.prestation.name}</strong>
              <span className="muted">
                {appointment.prestation.durationMinutes} min ·{' '}
                {formatEuroCents(appointment.prestation.priceCents)}
              </span>
            </div>
            <div className="mt-1">
              {formatOptionalFrenchDateTime(appointment.startAt)} -{' '}
              {formatOptionalFrenchDateTime(appointment.endAt)}
            </div>
            {appointment.status && <div className="muted mt-1">Statut : {appointment.status}</div>}
            {canCancel && (
              <button
                onClick={() => void handleCancel(appointment.id)}
                disabled={cancellingId === appointment.id}
                className="mt-2 rounded bg-red-600 px-3 py-1.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
              >
                {cancellingId === appointment.id ? 'Annulation...' : 'Annuler le rendez-vous'}
              </button>
            )}
          </li>
        );
      })}
    </ul>
  );

  const totalPastPages = Math.max(1, Math.ceil(past.length / PAST_APPOINTMENTS_PER_PAGE));
  const paginatedPast = past.slice(
    (pastPage - 1) * PAST_APPOINTMENTS_PER_PAGE,
    pastPage * PAST_APPOINTMENTS_PER_PAGE,
  );

  return (
    <SiteLayout>
      <PageContainer size="medium" title="Mes rendez-vous">
        <StableContent
          loading={loading}
          hasContent={upcoming.length > 0 || past.length > 0 || !loading}
          loadingLabel="Chargement des rendez-vous..."
        >
          {error && <FeedbackMessage>{error}</FeedbackMessage>}

          {!error && (
            <div className="grid gap-6">
              <section>
                <h2>À venir</h2>
                {upcoming.length === 0 ? (
                  <p className="muted">Aucun rendez-vous à venir.</p>
                ) : (
                  renderList(upcoming, true)
                )}
              </section>

              <section>
                <h2>Passés</h2>
                {past.length === 0 ? (
                  <p className="muted">Aucun rendez-vous passé.</p>
                ) : (
                  <>
                    {renderList(paginatedPast, false)}
                    {totalPastPages > 1 && (
                      <div className="mt-3 flex flex-wrap items-center gap-2">
                        <button
                          type="button"
                          className="site-header__link"
                          disabled={pastPage === 1}
                          onClick={() => setPastPage((page) => Math.max(1, page - 1))}
                        >
                          Précédent
                        </button>
                        <span className="muted">
                          Page {pastPage} sur {totalPastPages}
                        </span>
                        <button
                          type="button"
                          className="site-header__link"
                          disabled={pastPage === totalPastPages}
                          onClick={() => setPastPage((page) => Math.min(totalPastPages, page + 1))}
                        >
                          Suivant
                        </button>
                      </div>
                    )}
                  </>
                )}
              </section>
            </div>
          )}
        </StableContent>
      </PageContainer>
    </SiteLayout>
  );
};
