import './appointment-booking.css';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';
import { SiteLayout } from '../../../shared/components/SiteLayout';
import { PageContainer } from '../../../shared/components/PageContainer';
import { useDocumentTitle } from '../../../shared/hooks/useDocumentTitle';
import { bookAppointment, fetchAvailability, fetchPrestations } from '../api';
import type { AvailabilitySlot, Prestation } from '../types';
import FullCalendar from '@fullcalendar/react';
import type { DatesSetArg, CalendarApi } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';

import { startOfDay, format } from 'date-fns';
import { fr } from 'date-fns/locale';

const ymd = (d: Date) => format(startOfDay(d), 'yyyy-MM-dd');

export const AppointmentBookingPage = () => {
  useDocumentTitle('Prendre un rendez-vous');
  const { status } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const toast = useToast();

  const [step, setStep] = useState(1);
  const [prestations, setPrestations] = useState<Prestation[]>([]);
  const [selectedPrestation, setSelectedPrestation] = useState<Prestation | null>(null);
  const [slots, setSlots] = useState<AvailabilitySlot[]>([]);
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | null>(null);
  const [booking, setBooking] = useState(false);

  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'recap' | 'success'>('recap');

  const calendarRef = useRef<FullCalendar | null>(null);

  // --- Charger les prestations
  useEffect(() => {
    (async () => {
      const data = await fetchPrestations();
      setPrestations(data);
    })();
  }, []);

  // --- Charger la disponibilité dynamiquement (vue visible)
  const loadAvailabilityForView = async (start: Date, end: Date) => {
    if (!selectedPrestation) return;
    const data = await fetchAvailability({
      start: start.toISOString(),
      end: end.toISOString(),
      prestationId: selectedPrestation.id,
    });
    setSlots(data);
  };

  // --- Quand on change de mois / année
  const handleDatesSet = async (arg: DatesSetArg) => {
    if (selectedPrestation) {
      await loadAvailabilityForView(arg.start, arg.end);
    }
  };

  // --- Grouper les créneaux par jour
  const slotsByDay = useMemo(() => {
    const map = new Map<string, AvailabilitySlot[]>();
    for (const s of slots) {
      const key = ymd(new Date(s.start));
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(s);
    }
    return map;
  }, [slots]);

  // --- Colorer les jours disponibles
  const events = useMemo(() => {
    return Array.from(slotsByDay.keys()).map((day) => ({
      start: day,
      display: 'background',
      backgroundColor: '#c2f0c2',
    }));
  }, [slotsByDay]);

  // --- Clic sur un jour
  const handleDateClick = (info: { date: Date }) => {
    const key = ymd(info.date);
    const available = slotsByDay.get(key);
    if (!available || available.length === 0) return;
    setSelectedDate(info.date);
    setStep(3);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // --- Créneaux du jour sélectionné
  const daySlots = selectedDate ? slotsByDay.get(ymd(selectedDate)) ?? [] : [];

  // --- Réservation
  const handleBooking = async () => {
    if (!selectedSlot || !selectedPrestation) return;
    if (status !== 'authenticated') {
      toast.show('Connectez-vous pour confirmer votre rendez-vous.', { variant: 'info' });
      navigate('/login', {
        state: {
          redirectTo: '/appointments/book',
          redirectState: {
            bookingConfirm: {
              prestationId: selectedPrestation.id,
              slot: selectedSlot,
            },
          },
        },
      });
      return;
    }
    setBooking(true);
    try {
      await bookAppointment({
        prestationId: selectedPrestation.id,
        startAt: selectedSlot.start,
      });
      setModalMode('success');
    } finally {
      setBooking(false);
    }
  };

  // If redirected after login with a pending confirmation, restore UI state
  useEffect(() => {
    const state: any = location.state;
    if (state?.bookingConfirm && prestations.length > 0) {
      const p = prestations.find((pr) => pr.id === state.bookingConfirm.prestationId) ?? null;
      setSelectedPrestation(p);
      setSelectedSlot(state.bookingConfirm.slot ?? null);
      if (p && state.bookingConfirm.slot) {
        setStep(3);
        setModalMode('recap');
        setModalOpen(true);
      }
      // Clear history state so back button doesn't re-open
      navigate(location.pathname, { replace: true });
    }
  }, [location.state, prestations]);

  // --- Navigation personnalisée
  const getApi = (): CalendarApi | null => calendarRef.current?.getApi() ?? null;

  const goPrevMonth = () => getApi()?.incrementDate({ months: -1 });
  const goNextMonth = () => getApi()?.incrementDate({ months: 1 });
  const goPrevYear = () => getApi()?.incrementDate({ years: -1 });
  const goNextYear = () => getApi()?.incrementDate({ years: 1 });
  const goToday = () => getApi()?.today();

  // --- Ouvrir la modale automatiquement quand un créneau est choisi
  useEffect(() => {
    if (selectedSlot && step === 3) {
      setModalMode('recap');
      setModalOpen(true);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }, [selectedSlot, step]);

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Prendre un rendez-vous">
      <div className="progress-bar">Étape {step} sur 3</div>

      {/* Étape 1 — Choix de la prestation */}
      {step === 1 && (
        <div className="register-form-card">
          <h2>Étape 1 — Choisissez une prestation</h2>
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
              {selectedDate &&
                format(selectedDate, "EEEE dd MMMM yyyy", { locale: fr })}
            </small>
          </h2>

          {daySlots.length === 0 && <p>Aucun créneau disponible ce jour-là.</p>}

          <div className="slot-list">
            {daySlots.map((slot, i) => {
              const s = new Date(slot.start);
              const e = new Date(slot.end);
              const active =
                selectedSlot?.start === slot.start && selectedSlot?.end === slot.end;
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
        <div className="modal-backdrop" onClick={() => !booking && setModalOpen(false)}>
          <div
            className="modal-container"
            onClick={(e) => e.stopPropagation()} // empêcher fermeture au clic interne
          >
            {modalMode === 'recap' && (
              <>
                <h2>Récapitulatif du rendez-vous</h2>
                <ul className="recap-list">
                  <li><strong>Prestation :</strong> {selectedPrestation?.name}</li>
                  <li>
                    <strong>Date :</strong>{' '}
                    {selectedSlot &&
                      format(new Date(selectedSlot.start), "EEEE dd MMM yyyy", { locale: fr })}
                  </li>
                  <li>
                    <strong>Heure :</strong>{' '}
                    {selectedSlot &&
                      format(new Date(selectedSlot.start), 'HH:mm', { locale: fr })}{' '}
                    -{' '}
                    {selectedSlot &&
                      format(new Date(selectedSlot.end), 'HH:mm', { locale: fr })}
                  </li>
                  <li><strong>Durée :</strong> {selectedPrestation?.durationMinutes} min</li>
                  <li><strong>Tarif :</strong> {selectedPrestation?.priceCents! / 100} €</li>
                </ul>

                <div className="modal-actions">
                  <button onClick={() => setModalOpen(false)} className="register-form__back">
                    Annuler
                  </button>
                  <button
                    onClick={handleBooking}
                    disabled={booking}
                    className="register-form__submit"
                  >
                    {booking ? 'Réservation...' : 'Confirmer'}
                  </button>
                </div>
              </>
            )}

            {modalMode === 'success' && (
              <>
                <h2>Rendez-vous confirmé ✅</h2>
                <p>
                  Votre rendez-vous pour <strong>{selectedPrestation?.name}</strong> est confirmé le{' '}
                  {selectedSlot &&
                    format(new Date(selectedSlot.start), "EEEE dd MMM yyyy 'à' HH:mm", {
                      locale: fr,
                    })}
                  .
                </p>
                <button
                  onClick={() => {
                    setModalOpen(false);
                    setStep(1);
                    setSelectedPrestation(null);
                    setSelectedSlot(null);
                    setSelectedDate(null);
                    setSlots([]);
                  }}
                  className="register-form__submit"
                >
                  Fermer
                </button>
              </>
            )}
          </div>
        </div>
      )}
      </PageContainer>
    </SiteLayout>
  );
};
