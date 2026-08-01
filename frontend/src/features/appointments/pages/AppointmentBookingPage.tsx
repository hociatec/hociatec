import './appointment-booking.css';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { useMetaTags } from '@/shared/hooks/useMetaTags';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import { useAppointmentBooking } from '../hooks/useAppointmentBooking';
import { AppointmentBookingModal } from '@/features/appointments/components/AppointmentBookingModal';
import {
  AppointmentStepOne,
  AppointmentStepThree,
  AppointmentStepTwo,
} from '@/features/appointments/components/AppointmentBookingSections';
import { SITE_URL } from '@/shared/config/seoConfig';

const stepLabels = {
  1: 'Choix de la prestation',
  2: 'Choix du jour',
  3: 'Choix du créneau',
} as const;

export const AppointmentBookingPage = () => {
  useDocumentTitle('Prendre un rendez-vous');
  useMetaTags({
    title: 'Prendre un rendez-vous',
    description:
      'Choisissez une prestation Hociatec, sélectionnez un jour disponible puis confirmez votre créneau.',
    canonicalUrl: `${SITE_URL}/appointments/book`,
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
    calendarRef,
    events,
    daySlots,
    handleDatesSet,
    handleDateClick,
    handleBooking,
    goPrevMonth,
    goNextMonth,
    goPrevYear,
    goNextYear,
    goToday,
  } = useAppointmentBooking();

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        size="medium"
        eyebrow="Rendez-vous Hociatec"
        title="Prendre un rendez-vous"
        description="Choisissez une prestation, sélectionnez un jour disponible puis confirmez votre créneau."
      >
        <PublicPageSection className="space-y-6 p-5 sm:p-6">
          <div className="progress-bar" aria-live="polite">
            Étape {step} sur 3 · {stepLabels[step as keyof typeof stepLabels]}
          </div>

          {step === 1 && (
            <AppointmentStepOne
              prestations={prestations}
              prestationsError={prestationsError}
              selectedPrestation={selectedPrestation}
              setSelectedPrestation={setSelectedPrestation}
              onNext={() => setStep(2)}
            />
          )}

          {step === 2 && (
            <AppointmentStepTwo
              calendarRef={calendarRef}
              events={events}
              handleDatesSet={handleDatesSet}
              handleDateClick={handleDateClick}
              goPrevMonth={goPrevMonth}
              goNextMonth={goNextMonth}
              goPrevYear={goPrevYear}
              goNextYear={goNextYear}
              goToday={goToday}
              onBack={() => setStep(1)}
              plugins={[dayGridPlugin, interactionPlugin]}
              locale="fr"
              locales={[frLocale]}
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
        </PublicPageSection>
      </PublicPageShell>
    </SiteLayout>
  );
};
