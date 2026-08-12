import { useMemo } from 'react';
import './appointment-booking.css';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import { useAppointmentBooking } from '../hooks/useAppointmentBooking';
import { AppointmentBookingModal } from '@/features/appointments/components/AppointmentBookingModal';
import {
  AppointmentStepOne,
  AppointmentStepThree,
  AppointmentStepTwo,
} from '@/features/appointments/components/AppointmentBookingSections';
import { PRIVATE_ROBOTS_CONTENT, SITE_URL } from '@/shared/config/seoConfig';

const stepLabels = {
  1: 'Choix de la prestation',
  2: 'Choix du jour',
  3: 'Choix du créneau',
} as const;

const stepDescriptions = {
  1: 'Sélectionnez le service adapté à votre besoin.',
  2: 'Choisissez un jour réellement disponible dans le calendrier.',
  3: 'Validez un créneau précis avant la confirmation finale.',
} as const;

const bookingSteps = [1, 2, 3] as const;

export const AppointmentBookingPage = () => {
  useDocumentTitle('Prendre un rendez-vous');
  useMetaTags({
    title: 'Prendre un rendez-vous',
    description:
      'Choisissez une prestation Hociatec, sélectionnez un jour disponible puis confirmez votre créneau.',
    canonicalUrl: `${SITE_URL}/appointments/book`,
    robots: PRIVATE_ROBOTS_CONTENT,
  });
  const {
    step,
    setStep,
    prestations,
    prestationsError,
    selectedPrestation,
    setSelectedPrestation,
    setSlots,
    selectedDate,
    setSelectedDate,
    selectedSlot,
    setSelectedSlot,
    booking,
    modalOpen,
    setModalOpen,
    modalMode,
    daySlots,
    handleBooking,
    goPrevMonth,
    goNextMonth,
    goPrevYear,
    goNextYear,
    goToday,
    setVisibleMonth,
    slotsByDay,
    currentMonth,
  } = useAppointmentBooking();
  const availableDays = useMemo(() => {
    const keys = Array.from(slotsByDay.keys()).sort();

    return keys;
  }, [slotsByDay]);
  const handleAccessibleDaySelect = (isoDay: string) => {
    if (!isoDay) {
      return;
    }

    const parts = isoDay.split('-');
    if (parts.length !== 3) {
      return;
    }
    const [year, month, day] = parts;
    const parsedYear = Number(year);
    const parsedMonth = Number(month);
    const parsedDay = Number(day);
    if (Number.isNaN(parsedYear) || Number.isNaN(parsedMonth) || Number.isNaN(parsedDay)) {
      return;
    }

    setSelectedDate(new Date(parsedYear, parsedMonth - 1, parsedDay));
    setStep(3);
  };
  const handleGoStep2 = () => {
    setVisibleMonth(currentMonth);
    setStep(2);
  };
  const handleBackToStep1 = () => setStep(1);

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        title="Prendre un rendez-vous"
        description="Réservez votre créneau en trois étapes claires : prestation, jour disponible, puis horaire de passage."
      >
        <section className="grid gap-3 sm:grid-cols-3">
          {bookingSteps.map((stepNumber) => {
            const label = stepLabels[stepNumber];
            const numericStep = stepNumber;
            const isActive = step === numericStep;
            const isCompleted = step > numericStep;

            return (
              <div
                key={stepNumber}
                className={`rounded-3xl border p-5 shadow-sm transition ${
                  isActive
                    ? 'border-brand-200 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(236,245,255,0.96))]'
                    : isCompleted
                      ? 'border-emerald-200 bg-[linear-gradient(135deg,rgba(255,255,255,1),rgba(236,253,245,0.92))]'
                      : 'border-brand-100 bg-white'
                }`}
              >
                <div className="flex items-center gap-3">
                  <span
                    className={`inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold ${
                      isActive
                        ? 'bg-brand-900 text-white'
                        : isCompleted
                          ? 'bg-emerald-600 text-white'
                          : 'bg-brand-50 text-brand-800'
                    }`}
                  >
                    {isCompleted ? '✓' : stepNumber}
                  </span>
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-stone-500">
                      Étape {stepNumber}
                    </p>
                    <p className="text-sm font-semibold text-brand-950">{label}</p>
                  </div>
                </div>
                <p className="mt-3 text-sm leading-6 text-stone-600">
                  {stepDescriptions[numericStep as keyof typeof stepDescriptions]}
                </p>
              </div>
            );
          })}
        </section>

        <PublicPageSection className="overflow-hidden p-0">
          <div className="border-b border-brand-100 bg-[linear-gradient(135deg,rgba(11,100,216,0.08),rgba(255,255,255,0.9))] px-6 py-5">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <h2 className="text-2xl font-semibold text-brand-950">
                  {stepLabels[step as keyof typeof stepLabels]}
                </h2>
              </div>
              <p className="max-w-xl text-sm text-stone-600">
                Étape {step} sur 3. {stepDescriptions[step as keyof typeof stepDescriptions]}
              </p>
            </div>
          </div>

          <div className="space-y-6 p-5 sm:p-6">
            <div className="progress-bar" aria-live="polite">
              Étape {step} sur 3 · {stepLabels[step as keyof typeof stepLabels]}
            </div>

          {step === 1 && (
            <AppointmentStepOne
              prestations={prestations}
              prestationsError={prestationsError}
              selectedPrestation={selectedPrestation}
              setSelectedPrestation={setSelectedPrestation}
              onNext={handleGoStep2}
            />
          )}

          {step === 2 && (
            <AppointmentStepTwo
              onDaySelect={handleAccessibleDaySelect}
              availableDays={availableDays}
              currentMonth={currentMonth}
              goPrevMonth={goPrevMonth}
              goNextMonth={goNextMonth}
              goPrevYear={goPrevYear}
              goNextYear={goNextYear}
              goToday={goToday}
              selectedDate={selectedDate}
              onBack={handleBackToStep1}
            />
          )}

          {step === 3 && (
            <AppointmentStepThree
              daySlots={daySlots}
              selectedDate={selectedDate}
              selectedSlot={selectedSlot}
              setSelectedSlot={setSelectedSlot}
              onBack={() => setStep(2)}
            />
          )}

          {modalOpen && (
            <AppointmentBookingModal
              booking={booking}
              modalMode={modalMode}
              selectedPrestation={selectedPrestation}
              selectedSlot={selectedSlot}
              onClose={() => {
                setModalOpen(false);
                if (modalMode === 'success') {
                  setStep(1);
                  setSelectedPrestation(null);
                  setSelectedSlot(null);
                  setSelectedDate(null);
                  setSlots([]);
                }
              }}
              onConfirm={() => void handleBooking()}
            />
          )}
          </div>
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
