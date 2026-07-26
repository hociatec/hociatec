import './appointment-booking.css';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { PageContainer } from '../../../shared/components/PageContainer';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import { format } from 'date-fns';

import { fr } from 'date-fns/locale';
import { useAppointmentBooking } from '../hooks/useAppointmentBooking';
import { AppointmentBookingModal } from '@/features/appointments/components/AppointmentBookingModal';

export const AppointmentBookingPage = () => {
  useDocumentTitle('Prendre un rendez-vous');
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
      <PageContainer size="medium" title="Prendre un rendez-vous">
        <div className="progress-bar">Étape {step} sur 3</div>

        {/* Étape 1 — Choix de la prestation */}
        {step === 1 && (
          <div className="register-form-card">
            <h2>Étape 1 — Choisissez une prestation</h2>
            {prestationsError && (
              <div className="booking__alert" role="alert">
                {prestationsError}
              </div>
            )}
            {!prestationsError && prestations.length === 0 && (
              <p className="booking__empty">Aucune prestation disponible pour le moment.</p>
            )}
            {prestations.map((p) => (
              <label key={p.id} className="booking__checkbox">
                <input
                  type="radio"
                  name="prestation"
                  checked={selectedPrestation?.id === p.id}
                  onChange={() => setSelectedPrestation(p)}
                />
                {p.name} — {p.priceCents / 100} € ({p.durationMinutes} min)
              </label>
            ))}
            <div className="booking__actions">
              <button
                disabled={!selectedPrestation}
                onClick={() => setStep(2)}
                className="register-form__submit"
              >
                Suivant
              </button>
            </div>
          </div>
        )}

        {/* Étape 2 — Choix du jour */}
        {step === 2 && (
          <div className="register-form-card">
            <h2>Étape 2 — Choisissez un jour</h2>

            {/* Barre de navigation bien libellée */}
            <div className="calendar-nav">
              <button onClick={goPrevYear}>⟨ Année précédente</button>
              <button onClick={goPrevMonth}>← Mois précédent</button>
              <button onClick={goToday}>Aujourd’hui</button>
              <button onClick={goNextMonth}>Mois suivant →</button>
              <button onClick={goNextYear}>Année suivante ⟩</button>
            </div>

            <FullCalendar
              ref={calendarRef}
              plugins={[dayGridPlugin, interactionPlugin]}
              initialView="dayGridMonth"
              locales={[frLocale]}
              locale="fr"
              height="auto"
              dateClick={handleDateClick}
              datesSet={handleDatesSet}
              events={events}
            />

            <div className="booking__actions">
              <button onClick={() => setStep(1)} className="register-form__back">
                ← Étape précédente
              </button>
            </div>
          </div>
        )}

        {/* Étape 3 — Choix du créneau */}
        {step === 3 && (
          <div className="register-form-card">
            <h2>
              Étape 3 — Choisissez un créneau <br />
              <small>
                {selectedDate && format(selectedDate, 'EEEE dd MMMM yyyy', { locale: fr })}
              </small>
            </h2>

            {daySlots.length === 0 && <p>Aucun créneau disponible ce jour-là.</p>}

            <div className="slot-list">
              {daySlots.map((slot, i) => {
                const s = new Date(slot.start);
                const e = new Date(slot.end);
                const active = selectedSlot?.start === slot.start && selectedSlot?.end === slot.end;
                return (
                  <button
                    key={i}
                    className={`slot-card ${active ? 'active' : ''}`}
                    onClick={() => setSelectedSlot(slot)}
                  >
                    {format(s, 'HH:mm')} - {format(e, 'HH:mm')}
                  </button>
                );
              })}
            </div>

            <div className="booking__actions">
              <button onClick={() => setStep(2)} className="register-form__back">
                ← Revenir au calendrier
              </button>
            </div>
          </div>
        )}

        {/* --- Modale moderne --- */}
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
      </PageContainer>
    </SiteLayout>
  );
};
