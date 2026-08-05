import FullCalendar from '@fullcalendar/react';
import type { RefObject } from 'react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import type { EventInput } from '@fullcalendar/core';

import type { AvailabilitySlot, Prestation } from '@/features/appointments/types/appointments';
import { formatEuroCents } from '@/shared/lib/formatters';

type AppointmentStepOneProps = {
  prestations: Prestation[];
  prestationsError: string | null;
  selectedPrestation: Prestation | null;
  setSelectedPrestation: (prestation: Prestation) => void;
  onNext: () => void;
};

export const AppointmentStepOne = ({
  prestations,
  prestationsError,
  selectedPrestation,
  setSelectedPrestation,
  onNext,
}: AppointmentStepOneProps) => (
  <div className="register-form-card form-card-grid">
    <h2>Étape 1 — Choisissez une prestation</h2>
    {prestationsError ? (
      <div className="booking__alert" role="alert">
        {prestationsError}
      </div>
    ) : null}
    {!prestationsError && prestations.length === 0 ? (
      <p className="booking__empty">Aucune prestation disponible pour le moment.</p>
    ) : null}
    {prestations.map((prestation) => (
      <label key={prestation.id} className="booking__checkbox">
        <input
          type="radio"
          name="prestation"
          checked={selectedPrestation?.id === prestation.id}
          onChange={() => setSelectedPrestation(prestation)}
        />
        {prestation.name} — {formatEuroCents(prestation.priceCents)} ({prestation.durationMinutes} min)
      </label>
    ))}
    <div className="booking__actions">
      <button
        type="button"
        disabled={!selectedPrestation}
        onClick={onNext}
        className="register-form__submit"
      >
        Suivant
      </button>
    </div>
  </div>
);

type AppointmentStepTwoProps = {
  calendarRef: RefObject<FullCalendar | null>;
  events: EventInput[];
  handleDatesSet: (arg: Parameters<NonNullable<React.ComponentProps<typeof FullCalendar>['datesSet']>>[0]) => void | Promise<void>;
  handleDateClick: (arg: Parameters<NonNullable<React.ComponentProps<typeof FullCalendar>['dateClick']>>[0]) => void;
  goPrevMonth: () => void;
  goNextMonth: () => void;
  goPrevYear: () => void;
  goNextYear: () => void;
  goToday: () => void;
  onBack: () => void;
  plugins: React.ComponentProps<typeof FullCalendar>['plugins'];
  locale: string;
  locales: React.ComponentProps<typeof FullCalendar>['locales'];
};

export const AppointmentStepTwo = ({
  calendarRef,
  events,
  handleDatesSet,
  handleDateClick,
  goPrevMonth,
  goNextMonth,
  goPrevYear,
  goNextYear,
  goToday,
  onBack,
  plugins,
  locale,
  locales,
}: AppointmentStepTwoProps) => (
  <div className="register-form-card form-card-grid">
    <h2>Étape 2 — Choisissez un jour</h2>
    <div className="calendar-nav">
      <button type="button" onClick={goPrevYear}>⟨ Année précédente</button>
      <button type="button" onClick={goPrevMonth}>← Mois précédent</button>
      <button type="button" onClick={goToday}>Aujourd’hui</button>
      <button type="button" onClick={goNextMonth}>Mois suivant →</button>
      <button type="button" onClick={goNextYear}>Année suivante ⟩</button>
    </div>
    <FullCalendar
      ref={calendarRef}
      {...(plugins ? { plugins } : {})}
      initialView="dayGridMonth"
      {...(locales ? { locales } : {})}
      locale={locale}
      height="auto"
      dateClick={handleDateClick}
      datesSet={handleDatesSet}
      events={events}
    />
    <div className="booking__actions">
      <button type="button" onClick={onBack} className="register-form__back">
        ← Étape précédente
      </button>
    </div>
  </div>
);

type AppointmentStepThreeProps = {
  daySlots: AvailabilitySlot[];
  selectedDate: Date | null;
  selectedSlot: AvailabilitySlot | null;
  setSelectedSlot: (slot: AvailabilitySlot) => void;
  onBack: () => void;
};

export const AppointmentStepThree = ({
  daySlots,
  selectedDate,
  selectedSlot,
  setSelectedSlot,
  onBack,
}: AppointmentStepThreeProps) => (
  <div className="register-form-card form-card-grid">
    <h2>
      Étape 3 — Choisissez un créneau <br />
      <small>{selectedDate && format(selectedDate, 'EEEE dd MMMM yyyy', { locale: fr })}</small>
    </h2>
    {daySlots.length === 0 ? <p>Aucun créneau disponible ce jour-là.</p> : null}
    <div className="slot-list">
      {daySlots.map((slot, index) => {
        const start = new Date(slot.start);
        const end = new Date(slot.end);
        const active = selectedSlot?.start === slot.start && selectedSlot?.end === slot.end;
        return (
          <button
            key={index}
            type="button"
            className={`slot-card ${active ? 'active' : ''}`}
            onClick={() => setSelectedSlot(slot)}
          >
            {format(start, 'HH:mm')} - {format(end, 'HH:mm')}
          </button>
        );
      })}
    </div>
    <div className="booking__actions">
      <button type="button" onClick={onBack} className="register-form__back">
        ← Revenir au calendrier
      </button>
    </div>
  </div>
);
