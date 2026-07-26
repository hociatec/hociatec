import { useEffect, useMemo, useRef, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import type { DatesSetArg, CalendarApi } from '@fullcalendar/core';
import type FullCalendar from '@fullcalendar/react';
import { format, startOfDay } from 'date-fns';
import { bookAppointment, fetchAvailability, fetchPrestations } from '../api/appointmentsApi';
import type { AvailabilitySlot, Prestation } from '../types/appointments';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useToast } from '@/shared/components/ui/toast';

const ymd = (date: Date) => format(startOfDay(date), 'yyyy-MM-dd');
type BookingState = { bookingConfirm?: { prestationId: number; slot: AvailabilitySlot } };
export const useAppointmentBooking = () => {
  const { status } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const toast = useToast();
  const [step, setStep] = useState(1);
  const [prestations, setPrestations] = useState<Prestation[]>([]);
  const [prestationsError, setPrestationsError] = useState<string | null>(null);
  const [selectedPrestation, setSelectedPrestation] = useState<Prestation | null>(null);
  const [slots, setSlots] = useState<AvailabilitySlot[]>([]);
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | null>(null);
  const [booking, setBooking] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'recap' | 'success'>('recap');
  const calendarRef = useRef<FullCalendar | null>(null);
  useEffect(() => {
    void fetchPrestations()
      .then(setPrestations)
      .catch((reason: Error) =>
        setPrestationsError(
          reason.message || 'Impossible de charger les prestations pour le moment.',
        ),
      );
  }, []);
  const loadAvailabilityForView = async (start: Date, end: Date) => {
    if (!selectedPrestation) return;
    try {
      setSlots(
        await fetchAvailability({
          start: start.toISOString(),
          end: end.toISOString(),
          prestationId: selectedPrestation.id,
        }),
      );
    } catch (reason) {
      toast.show(
        reason instanceof Error
          ? reason.message
          : 'Impossible de charger les créneaux disponibles.',
        { variant: 'error' },
      );
      setSlots([]);
    }
  };
  const handleDatesSet = async (arg: DatesSetArg) => {
    await loadAvailabilityForView(arg.start, arg.end);
  };
  const slotsByDay = useMemo(() => {
    const map = new Map<string, AvailabilitySlot[]>();
    slots.forEach((slot) => {
      const key = ymd(new Date(slot.start));
      map.set(key, [...(map.get(key) ?? []), slot]);
    });
    return map;
  }, [slots]);
  const events = useMemo(
    () =>
      Array.from(slotsByDay.keys()).map((day) => ({
        start: day,
        display: 'background',
        backgroundColor: '#c2f0c2',
      })),
    [slotsByDay],
  );
  const handleDateClick = (info: { date: Date }) => {
    if (!slotsByDay.get(ymd(info.date))?.length) return;
    setSelectedDate(info.date);
    setStep(3);
  };
  const daySlots = selectedDate ? (slotsByDay.get(ymd(selectedDate)) ?? []) : [];
  const handleBooking = async () => {
    if (!selectedSlot || !selectedPrestation) return;
    if (status !== 'authenticated') {
      toast.show('Connectez-vous pour confirmer votre rendez-vous.', { variant: 'info' });
      navigate('/login', {
        state: {
          redirectTo: '/appointments/book',
          redirectState: {
            bookingConfirm: { prestationId: selectedPrestation.id, slot: selectedSlot },
          },
        },
      });
      return;
    }
    setBooking(true);
    try {
      await bookAppointment({ prestationId: selectedPrestation.id, startAt: selectedSlot.start });
      setModalMode('success');
    } finally {
      setBooking(false);
    }
  };
  useEffect(() => {
    const bookingConfirm = (location.state as BookingState | null)?.bookingConfirm;
    if (!bookingConfirm || prestations.length === 0) return;
    const prestation = prestations.find((item) => item.id === bookingConfirm.prestationId) ?? null;
    setSelectedPrestation(prestation);
    setSelectedSlot(bookingConfirm.slot ?? null);
    if (prestation && bookingConfirm.slot) {
      setStep(3);
      setModalMode('recap');
      setModalOpen(true);
    }
    navigate(location.pathname, { replace: true });
  }, [location.pathname, location.state, navigate, prestations]);
  useEffect(() => {
    if (selectedSlot && step === 3) {
      setModalMode('recap');
      setModalOpen(true);
    }
  }, [selectedSlot, step]);
  const getApi = (): CalendarApi | null => calendarRef.current?.getApi() ?? null;
  return {
    status,
    step,
    setStep,
    prestations,
    prestationsError,
    selectedPrestation,
    setSelectedPrestation,
    slots,
    setSlots,
    selectedDate,
    setSelectedDate,
    selectedSlot,
    setSelectedSlot,
    booking,
    modalOpen,
    setModalOpen,
    modalMode,
    setModalMode,
    calendarRef,
    slotsByDay,
    events,
    daySlots,
    handleDatesSet,
    handleDateClick,
    handleBooking,
    goPrevMonth: () => getApi()?.incrementDate({ months: -1 }),
    goNextMonth: () => getApi()?.incrementDate({ months: 1 }),
    goPrevYear: () => getApi()?.incrementDate({ years: -1 }),
    goNextYear: () => getApi()?.incrementDate({ years: 1 }),
    goToday: () => getApi()?.today(),
  };
};
