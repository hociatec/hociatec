import { useMemo } from 'react';
import {
  addDays,
  endOfMonth,
  endOfWeek,
  format,
  isBefore,
  isAfter,
  isValid,
  isSameMonth,
  isToday,
  startOfDay,
  parseISO,
  startOfMonth,
  startOfWeek,
} from 'date-fns';
import { fr } from 'date-fns/locale';

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
  onDaySelect: (isoDay: string) => void;
  availableDays: string[];
  selectedDate: Date | null;
  currentMonth: Date;
  goPrevMonth: () => void;
  goNextMonth: () => void;
  goPrevYear: () => void;
  goNextYear: () => void;
  goToday: () => void;
  onBack: () => void;
};

const formatDateId = (day: Date) => {
  if (!isValid(day)) {
    return '';
  }

  return format(day, 'yyyy-MM-dd');
};

const toDate = (iso: string) => {
  const date = parseISO(iso);
  return Number.isNaN(date.getTime()) ? null : date;
};

const safeFormat = (date: Date, pattern: string, fallback = 'Date invalide') => {
  if (!isValid(date)) {
    return fallback;
  }

  return format(date, pattern, { locale: fr });
};

export const AppointmentStepTwo = ({
  onDaySelect,
  availableDays,
  selectedDate,
  currentMonth,
  goPrevMonth,
  goNextMonth,
  goPrevYear,
  goNextYear,
  goToday,
  onBack,
}: AppointmentStepTwoProps) => {
  const selectedDateValue = selectedDate ? safeFormat(selectedDate, 'yyyy-MM-dd', '') : '';
  const currentMonthStart = isValid(currentMonth) ? startOfMonth(currentMonth) : startOfMonth(new Date());
  const currentMonthTime = currentMonthStart.getTime();
  const today = startOfDay(new Date());
  const monthLabel = safeFormat(currentMonthStart, 'MMMM yyyy');
  const availableDaysSet = useMemo(() => new Set(availableDays), [availableDays]);
  const availableInMonth = useMemo(
    () =>
      availableDays.filter((day) => {
        const parsed = toDate(day);
        return parsed !== null && isSameMonth(parsed, currentMonthStart);
      }),
    [availableDays, currentMonthTime],
  );

  const calendarDays = useMemo(() => {
    const first = startOfWeek(currentMonthStart, { weekStartsOn: 1 });
    const last = endOfWeek(endOfMonth(currentMonthStart), { weekStartsOn: 1 });

    const days: Date[] = [];
    for (let day = first; !isAfter(day, last); day = addDays(day, 1)) {
      days.push(day);
    }

    return days;
  }, [currentMonthTime]);

  const weekDayHeaders = useMemo(() => {
    const start = startOfWeek(currentMonthStart, { weekStartsOn: 1 });
    return Array.from({ length: 7 }, (_, index) =>
      format(addDays(start, index), 'EEEEEE', { locale: fr }),
    );
  }, [currentMonthTime]);

  return (
    <div className="register-form-card form-card-grid">
      <h2>Étape 2 — Choisissez un jour</h2>
      <p id="appointment-day-help" className="sr-only">
        Utilisez les boutons du calendrier pour choisir un jour disponible, puis passez à l’étape suivante.
      </p>
      <div aria-live="polite" role="status" className="sr-only">
        {availableInMonth.length > 0
          ? `${availableInMonth.length} jour(s) disponible(s) en ${monthLabel}.`
          : `Aucun jour disponible en ${monthLabel}.`}
        {selectedDate
          ? ` Jour sélectionné: ${safeFormat(selectedDate, 'EEEE dd MMMM yyyy', 'Date invalide')}.`
          : ' Aucun jour sélectionné.'}
      </div>

      <div className="calendar-nav" role="group" aria-label="Navigation du calendrier">
        <button type="button" onClick={goPrevYear} aria-label="Aller à l'année précédente">
          ⟨ Année précédente
        </button>
        <button type="button" onClick={goPrevMonth} aria-label="Aller au mois précédent">
          ← Mois précédent
        </button>
        <button type="button" onClick={goToday} aria-label="Revenir à aujourd'hui">
          Aujourd’hui
        </button>
        <button type="button" onClick={goNextMonth} aria-label="Aller au mois suivant">
          Mois suivant →
        </button>
        <button type="button" onClick={goNextYear} aria-label="Aller à l'année suivante">
          Année suivante ⟩
        </button>
      </div>

      <div className="booking-calendar" aria-label={`Calendrier des jours disponibles de ${monthLabel}`}>
        <h3 className="booking-calendar__month" aria-live="polite">
          {monthLabel}
        </h3>
        <table className="booking-calendar__grid" role="grid" aria-labelledby="appointment-calendar-title">
          <caption id="appointment-calendar-title" className="sr-only">
            Tableau des jours du mois affiché. Utilisez Tab puis Entrée pour choisir un jour disponible.
          </caption>
          <thead>
            <tr>
              {weekDayHeaders.map((weekday) => (
                <th key={weekday} scope="col">
                  {weekday}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {Array.from({ length: Math.ceil(calendarDays.length / 7) }, (_, rowIndex) => {
              const rowDays = calendarDays.slice(rowIndex * 7, (rowIndex + 1) * 7);
              return (
                <tr key={rowIndex}>
                  {rowDays.map((day) => {
                    const dayId = formatDateId(day);
                    const parsed = dayId ? toDate(dayId) : null;
                    const isDisabledByDate = parsed !== null && isBefore(startOfDay(parsed), today);
                    const hasSlots = Boolean(dayId && availableDaysSet.has(dayId) && parsed !== null);
                    const isCurrentMonth = isSameMonth(day, currentMonthStart);
                    const isCurrentDay = isToday(day);
                    const isSelected = selectedDateValue === dayId;
                    const isDisabled = isDisabledByDate || !hasSlots;
                    const labelDate = parsed
                      ? safeFormat(parsed, 'EEEE dd MMMM yyyy')
                      : `Jour ${dayId || 'invalide'}`;
                    const statusLabel = isDisabledByDate
                      ? 'Jour dépassé, non cliquable.'
                      : hasSlots
                        ? 'Créneaux disponibles, bouton cliquable.'
                        : 'Aucun créneau disponible ce jour-là.';

                    if (!isCurrentMonth) {
                      return (
                        <td
                          key={dayId ?? `${day.getTime()}`}
                          role="presentation"
                          className="booking-calendar__day-cell booking-calendar__day-cell--outside"
                          aria-hidden="true"
                          title=""
                        />
                      );
                    }

                    if (isDisabledByDate) {
                      return (
                        <td key={dayId ?? `${day.getTime()}`} role="gridcell" aria-disabled="true">
                          <span className={`booking-calendar__day ${
                            isCurrentDay ? 'booking-calendar__day--today' : ''
                          }`}>
                            {safeFormat(day, 'd', '')}
                          </span>
                        </td>
                      );
                    }

                    return (
                      <td
                        key={dayId ?? `${day.getTime()}`}
                        role="gridcell"
                        aria-disabled={isDisabled}
                      >
                        <button
                          type="button"
                          className={`booking-calendar__day ${isCurrentMonth ? '' : 'booking-calendar__day--outside'} ${
                            isCurrentDay ? 'booking-calendar__day--today' : ''
                          } ${isSelected ? 'booking-calendar__day--selected' : ''}`}
                          onClick={() => dayId && onDaySelect(dayId)}
                          disabled={isDisabled}
                          aria-label={`${labelDate}. ${statusLabel} ${isSelected ? 'Jour sélectionné.' : ''}`}
                          aria-pressed={isSelected}
                        >
                          {safeFormat(day, 'd', '')}
                        </button>
                      </td>
                    );
                  })}
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="booking__actions">
        <button type="button" onClick={onBack} className="register-form__back">
          ← Étape précédente
        </button>
      </div>
    </div>
  );
};

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
      <small>{selectedDate && safeFormat(selectedDate, 'EEEE dd MMMM yyyy')}</small>
    </h2>
    {daySlots.length === 0 ? <p>Aucun créneau disponible ce jour-là.</p> : null}
    <div className="slot-list" role="list" aria-label="Créneaux disponibles">
      {daySlots.map((slot, index) => {
        const start = new Date(slot.start);
        const end = new Date(slot.end);
        const active = selectedSlot?.start === slot.start && selectedSlot?.end === slot.end;
        const startLabel = Number.isNaN(start.getTime()) ? '' : `${format(start, 'HH:mm')}`;
        const endLabel = Number.isNaN(end.getTime()) ? '' : `${format(end, 'HH:mm')}`;
        const label = `${startLabel} - ${endLabel}`;

        return (
          <button
            key={index}
            type="button"
            className={`slot-card slot-list__item ${active ? 'active' : ''}`}
            onClick={() => setSelectedSlot(slot)}
            role="listitem"
            aria-label={label ? `${label} (créneau)` : 'Créneau indisponible'}
          >
            <span>{label || 'Créneau indisponible'}</span>
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
