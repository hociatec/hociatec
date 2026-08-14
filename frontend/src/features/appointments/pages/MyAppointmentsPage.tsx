import { useEffect, useRef, useState } from 'react';
import { useLocation, useNavigate } from 'react-router';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import type { AppointmentItem } from '../types/appointments';
import { useMyAppointments } from '../hooks/useMyAppointments';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { FeedbackMessage, StableContent } from '@/shared/components/ui/page-state';
import { formatEuroCents, parseApiDate } from '@/shared/lib/formatters';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';

const PAST_APPOINTMENTS_PER_PAGE = 5;
const APPOINTMENT_TIME_ZONE = 'Europe/Paris';
type AppointmentLocationState = { appointmentFlashMessage?: string };

const formatAppointmentDate = (value: string) => {
  const date = parseApiDate(value);
  if (!date) {
    return 'Date inconnue';
  }

  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: APPOINTMENT_TIME_ZONE,
  }).format(date);
};

const formatAppointmentTime = (value: string) => {
  const date = parseApiDate(value);
  if (!date) {
    return '--:--';
  }

  return new Intl.DateTimeFormat('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: APPOINTMENT_TIME_ZONE,
  }).format(date);
};

const getStatusBadgeClassName = (status?: string) => {
  switch (status) {
    case 'Annulé':
      return 'border border-red-200 bg-[linear-gradient(135deg,rgba(254,242,242,0.98),rgba(254,226,226,0.9))] text-red-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.65)]';
    case 'Confirmé':
      return 'border border-emerald-200 bg-[linear-gradient(135deg,rgba(236,253,245,0.98),rgba(209,250,229,0.92))] text-emerald-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)]';
    case 'En attente':
      return 'border border-amber-200 bg-[linear-gradient(135deg,rgba(255,251,235,0.98),rgba(254,243,199,0.92))] text-amber-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)]';
    default:
      return 'border border-brand-200 bg-[linear-gradient(135deg,rgba(255,255,255,0.98),rgba(240,247,255,0.92))] text-stone-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.72)]';
  }
};

export const MyAppointmentsPage = () => {
  useDocumentTitle('Mes rendez-vous');

  const navigate = useNavigate();
  const location = useLocation();
  const toast = useToast();
  const flashedLocationKeys = useRef(new Set<string>());
  const [pastPage, setPastPage] = useState(1);
  const { loading, error, upcoming, past, cancellingId, cancel } = useMyAppointments();
  const confirm = useConfirm();

  useEffect(() => {
    const state = location.state as AppointmentLocationState | null;
    const message = state?.appointmentFlashMessage;
    if (!message) {
      return;
    }

    const flashKey = `${location.key}:${message}`;
    if (flashedLocationKeys.current.has(flashKey)) {
      return;
    }

    flashedLocationKeys.current.add(flashKey);
    toast.show(message, { variant: 'success' });
  }, [location.key, location.state, toast]);

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

  const renderList = (
    items: AppointmentItem[],
    showCancelButton = false,
    tone: 'upcoming' | 'past' = 'upcoming',
  ) => (
    <ul className="grid list-none gap-3 p-0">
      {items.map((appointment) => {
        const isCancelled = appointment.status === 'Annulé';
        const canCancel = showCancelButton && !isCancelled;
        const canReschedule = showCancelButton && !isCancelled && appointment.isReschedulable !== false;
        const statusLabel = appointment.status ?? 'Planifié';
        const isPastTone = tone === 'past';

        return (
          <li
            key={appointment.id}
            className={`rounded-3xl border p-5 ${
              isPastTone
                ? 'border-stone-200 bg-[linear-gradient(135deg,rgba(255,255,255,0.98),rgba(245,245,244,0.96))] shadow-[0_14px_36px_rgba(28,25,23,0.05)]'
                : 'border-brand-100 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(247,251,255,0.96))] shadow-[0_18px_50px_rgba(7,24,47,0.07)]'
            }`}
          >
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <div className="space-y-3">
                <p
                  className={`text-xs font-semibold uppercase tracking-[0.24em] ${
                    isPastTone ? 'text-stone-500' : 'text-brand-700'
                  }`}
                >
                  {appointment.prestation.name}
                </p>
                <div className="space-y-1">
                  <div className="text-2xl font-semibold text-brand-950">
                    {formatAppointmentDate(appointment.startAt)}
                  </div>
                  <div className="text-base font-medium text-stone-700">
                    {formatAppointmentTime(appointment.startAt)} – {formatAppointmentTime(appointment.endAt)}
                  </div>
                </div>
                <dl className="grid gap-2 text-sm text-stone-700">
                  <div className="flex items-center gap-2">
                    <dt className="min-w-20 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                      Durée
                    </dt>
                    <dd>{appointment.prestation.durationMinutes} min</dd>
                  </div>
                  <div className="flex items-center gap-2">
                    <dt className="min-w-20 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                      Tarif
                    </dt>
                    <dd>{formatEuroCents(appointment.prestation.priceCents)}</dd>
                  </div>
                  <div className="flex items-center gap-2">
                    <dt className="min-w-20 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                      Statut
                    </dt>
                    <dd>
                      <span
                        className={`inline-flex min-w-[8.5rem] items-center justify-center rounded-full px-3 py-1.5 text-[0.72rem] font-semibold uppercase tracking-[0.16em] ${getStatusBadgeClassName(statusLabel)}`}
                      >
                        {statusLabel}
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>

              <div className="flex flex-col items-start gap-3 md:items-end">
                <span
                  className={`rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] ${
                    isPastTone
                      ? 'border-stone-200 bg-white text-stone-600'
                      : 'border-brand-100 bg-brand-50 text-brand-800'
                  }`}
                >
                  {isPastTone ? 'Historique' : 'Réservation'}
                </span>
                {showCancelButton && (
                  <div className="flex flex-wrap gap-2">
                    {canReschedule && (
                      <button
                        onClick={() =>
                          navigate('/appointments/book', {
                            state: {
                              reschedule: {
                                appointmentId: appointment.id,
                                prestationId: appointment.prestation.id,
                              },
                            },
                          })
                        }
                        className="inline-flex rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-800 transition hover:border-brand-300 hover:bg-brand-100"
                      >
                        Reporter
                      </button>
                    )}
                    {canCancel && (
                      <button
                        onClick={() => void handleCancel(appointment.id)}
                        disabled={cancellingId === appointment.id}
                        className="inline-flex rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                      >
                        {cancellingId === appointment.id ? 'Annulation...' : 'Annuler le rendez-vous'}
                      </button>
                    )}
                  </div>
                )}
              </div>
            </div>
          </li>
        );
      })}
    </ul>
  );

  const totalPastPages = clampAtLeast(Math.ceil(past.length / PAST_APPOINTMENTS_PER_PAGE), 1);
  const paginatedPast = past.slice(
    (pastPage - 1) * PAST_APPOINTMENTS_PER_PAGE,
    pastPage * PAST_APPOINTMENTS_PER_PAGE,
  );
  const totalAppointments = upcoming.length + past.length;

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        title="Mes rendez-vous"
        description="Retrouvez vos créneaux confirmés, suivez leur statut et gardez un historique clair de vos rendez-vous."
      >
        <StableContent
          loading={loading}
          hasContent={upcoming.length > 0 || past.length > 0 || !loading}
          loadingLabel="Chargement des rendez-vous..."
        >
          {error && <FeedbackMessage>{error}</FeedbackMessage>}

          {!error && (
            <div className="grid gap-6">
              <section className="grid gap-3 sm:grid-cols-3">
                <div className="rounded-3xl border border-brand-100 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(239,247,255,0.92))] p-5 shadow-sm">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">À venir</p>
                  <p className="mt-2 text-3xl font-semibold text-brand-950">{upcoming.length}</p>
                  <p className="mt-1 text-sm text-stone-600">Rendez-vous encore planifiés</p>
                </div>
                <div className="rounded-3xl border border-brand-100 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(250,250,249,0.96))] p-5 shadow-sm">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Historique</p>
                  <p className="mt-2 text-3xl font-semibold text-brand-950">{past.length}</p>
                  <p className="mt-1 text-sm text-stone-600">Rendez-vous déjà passés ou clôturés</p>
                </div>
                <div className="rounded-3xl border border-brand-100 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(244,247,250,0.96))] p-5 shadow-sm">
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Total</p>
                  <p className="mt-2 text-3xl font-semibold text-brand-950">{totalAppointments}</p>
                  <p className="mt-1 text-sm text-stone-600">Rendez-vous enregistrés sur votre compte</p>
                </div>
              </section>

              <PublicPageSection className="overflow-hidden p-0">
                <div className="border-b border-brand-100 bg-[linear-gradient(135deg,rgba(11,100,216,0.08),rgba(255,255,255,0.9))] px-6 py-5">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">
                        Prochains créneaux
                      </p>
                      <h2 className="mt-2 text-2xl font-semibold text-brand-950">À venir</h2>
                    </div>
                    <p className="max-w-xl text-sm text-stone-600">
                      Vos rendez-vous actifs restent visibles en priorité, avec leur créneau, leur tarif et leur statut.
                    </p>
                  </div>
                </div>
                <div className="p-6">
                {upcoming.length === 0 ? (
                  <p className="text-sm text-stone-600">Aucun rendez-vous à venir.</p>
                ) : (
                  renderList(upcoming, true, 'upcoming')
                )}
                </div>
              </PublicPageSection>

              <PublicPageSection className="overflow-hidden p-0">
                <div className="border-b border-brand-100 bg-[linear-gradient(135deg,rgba(120,113,108,0.08),rgba(255,255,255,0.92))] px-6 py-5">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.24em] text-stone-500">
                        Archive
                      </p>
                      <h2 className="mt-2 text-2xl font-semibold text-brand-950">Passés</h2>
                    </div>
                    <p className="max-w-xl text-sm text-stone-600">
                      Conservez une lecture simple de votre historique sans mélanger les rendez-vous déjà terminés avec les créneaux à venir.
                    </p>
                  </div>
                </div>
                <div className="p-6">
                {past.length === 0 ? (
                  <p className="text-sm text-stone-600">Aucun rendez-vous passé.</p>
                ) : (
                  <>
                    {renderList(paginatedPast, false, 'past')}
                    {totalPastPages > 1 && (
                      <div className="mt-3 flex flex-wrap items-center gap-2">
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                          disabled={pastPage === 1}
                          onClick={() => setPastPage((page) => clampAtLeast(page - 1, 1))}
                        >
                          Précédent
                        </button>
                        <span className="text-sm text-stone-600">
                          Page {pastPage} sur {totalPastPages}
                        </span>
                        <button
                          type="button"
                          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                          disabled={pastPage === totalPastPages}
                          onClick={() => setPastPage((page) => clampWithin(page + 1, 1, totalPastPages))}
                        >
                          Suivant
                        </button>
                      </div>
                    )}
                  </>
                )}
                </div>
              </PublicPageSection>
            </div>
          )}
        </StableContent>
      </PublicPageShell>
    </SiteLayout>
  );
};
