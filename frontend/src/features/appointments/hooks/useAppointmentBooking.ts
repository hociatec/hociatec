import { useEffect, useMemo, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useLocation, useNavigate } from 'react-router';
import type { DatesSetArg, CalendarApi } from '@fullcalendar/core';
import type FullCalendar from '@fullcalendar/react';
import { format, startOfDay } from 'date-fns';
import { bookAppointment, fetchAvailability, fetchPrestations } from '../api/appointmentsApi';
import type { AvailabilitySlot, Prestation } from '../types/appointments';
import { useAuth } from '@/features/auth/publicApi';
import { useToast } from '@/shared/components/ui/toast';
import { appointmentQueryKeys } from '@/shared/lib/queryKeys';

const ymd = (date: Date) => format(startOfDay(date), 'yyyy-MM-dd');
type BookingState = { bookingConfirm?: { prestationId: number; slot: AvailabilitySlot } };
export const useAppointmentBooking = () => {
  const { status } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [step, setStep] = useState(1);
  const [selectedPrestation, setSelectedPrestation] = useState<Prestation | null>(null);
  const [slots, setSlots] = useState<AvailabilitySlot[]>([]);
  const [availabilityRange, setAvailabilityRange] = useState<{ start: string; end: string } | null>(
    null,
  );
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'recap' | 'success'>('recap');
  const calendarRef = useRef<FullCalendar | null>(null);
  const prestationsQuery = useQuery<Prestation[], Error>({
    queryKey: appointmentQueryKeys.prestations(),
    queryFn: fetchPrestations,
  });
  const availabilityQuery = useQuery<AvailabilitySlot[], Error>({
    queryKey: appointmentQueryKeys.availability(
      selectedPrestation?.id ?? null,
      availabilityRange?.start ?? '',
      availabilityRange?.end ?? '',
    ),
    queryFn: () =>
      fetchAvailability({
        start: availabilityRange?.start ?? '',
        end: availabilityRange?.end ?? '',
        prestationId: selectedPrestation?.id ?? 0,
      }),
    enabled: Boolean(selectedPrestation && availabilityRange),
  });
  const bookingMutation = useMutation({
    mutationFn: bookAppointment,
    onSuccess: () => {
      setModalMode('success');
      void queryClient.invalidateQueries({ queryKey: appointmentQueryKeys.mine() });
    },
  });
  const prestations = prestationsQuery.data ?? [];
  const prestationsError = prestationsQuery.error
    ? prestationsQuery.error.message || 'Impossible de charger les prestations pour le moment.'
    : null;

  useEffect(() => {
    if (availabilityQuery.data) {
      setSlots(availabilityQuery.data);
    }
  }, [availabilityQuery.data]);

  useEffect(() => {
    if (availabilityQuery.error) {
      toast.show(
        availabilityQuery.error.message || 'Impossible de charger les créneaux disponibles.',
        { variant: 'error' },
      );
      setSlots([]);
    }
  }, [availabilityQuery.error, toast]);

  const handleDatesSet = async (arg: DatesSetArg) => {
    setAvailabilityRange({ start: arg.start.toISOString(), end: arg.end.toISOString() });
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
    bookingMutation.mutate({ prestationId: selectedPrestation.id, startAt: selectedSlot.start });
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
    booking: bookingMutation.isPending,
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
