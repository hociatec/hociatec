import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useLocation, useNavigate } from 'react-router';
import { addDays, addMonths, format, isAfter, isBefore, startOfDay, startOfMonth } from 'date-fns';
import { bookAppointment, fetchAvailability, fetchPrestations, rescheduleAppointment } from '../api/appointmentsApi';
import type { AvailabilitySlot, Prestation } from '../types/appointments';
import { useAuth } from '@/features/auth/publicApi';
import { useToast } from '@/shared/components/ui/toast';
import { appointmentQueryKeys } from '@/features/appointments/queryKeys';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

const ymd = (date: Date) => format(startOfDay(date), 'yyyy-MM-dd');

const formatWithOffset = (date: Date) => format(date, "yyyy-MM-dd'T'HH:mm:ssXXX");
type BookingState = { bookingConfirm?: { prestationId: number; slot: AvailabilitySlot } };
type RescheduleState = { reschedule?: { appointmentId: number; prestationId: number } };

const monthRange = (month: Date) => {
  const today = startOfDay(new Date());
  const monthStart = startOfMonth(month);
  const start = isBefore(monthStart, today) ? today : monthStart;
  const monthEnd = addMonths(monthStart, 1);
  const end = isAfter(monthEnd, start) ? monthEnd : addDays(start, 1);

    return {
    start: formatWithOffset(start),
    end: formatWithOffset(end),
  };
};

export const useAppointmentBooking = () => {
  const { status } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [step, setStep] = useState(1);
  const [selectedPrestation, setSelectedPrestation] = useState<Prestation | null>(null);
  const [slots, setSlots] = useState<AvailabilitySlot[]>([]);
  const [availabilityRange, setAvailabilityRange] = useState<{ start: string; end: string } | null>(null);
  const [selectedDate, setSelectedDate] = useState<Date | null>(null);
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'recap' | 'submitting'>('recap');
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [rescheduleAppointmentId, setRescheduleAppointmentId] = useState<number | null>(null);
  const resetFlow = () => {
    setStep(1);
    setSelectedPrestation(null);
    setSelectedDate(null);
    setSelectedSlot(null);
    setSlots([]);
    setRescheduleAppointmentId(null);
    setModalMode('recap');
    setSubmitError(null);
  };
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
      void queryClient.invalidateQueries({ queryKey: appointmentQueryKeys.mine() });
      setModalOpen(false);
      resetFlow();
      navigate('/appointments/me', {
        replace: true,
        state: { appointmentFlashMessage: 'Votre rendez-vous est confirmé.' },
      });
    },
    onError: (error) => {
      const message = getHttpErrorMessage(error, 'Impossible de confirmer le rendez-vous.');
      setModalMode('recap');
      setSubmitError(message);
    },
  });
  const rescheduleMutation = useMutation({
    mutationFn: rescheduleAppointment,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: appointmentQueryKeys.mine() });
      setModalOpen(false);
      resetFlow();
      navigate('/appointments/me', {
        replace: true,
        state: { appointmentFlashMessage: 'Votre rendez-vous a bien été reporté.' },
      });
    },
    onError: (error) => {
      const message = getHttpErrorMessage(error, 'Impossible de reporter le rendez-vous.');
      setModalMode('recap');
      setSubmitError(message);
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

  useEffect(() => {
    if (!selectedPrestation) {
      setAvailabilityRange(null);
      setSlots([]);
      return;
    }

    setAvailabilityRange(monthRange(new Date()));
  }, [selectedPrestation]);

  const slotsByDay = useMemo(() => {
    const map = new Map<string, AvailabilitySlot[]>();
    slots.forEach((slot) => {
      const key = ymd(new Date(slot.start));
      map.set(key, [...(map.get(key) ?? []), slot]);
    });
    return map;
  }, [slots]);
  const daySlots = selectedDate ? (slotsByDay.get(ymd(selectedDate)) ?? []) : [];
  const currentMonth = useMemo(
    () => (availabilityRange ? startOfMonth(new Date(availabilityRange.start)) : startOfMonth(new Date())),
    [availabilityRange],
  );
  const setMonth = (date: Date) => {
    const range = monthRange(date);
    setAvailabilityRange(range);
  };
  const handleBooking = async () => {
    if (!selectedSlot || !selectedPrestation) return;
    setSubmitError(null);
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
    if (rescheduleAppointmentId !== null) {
      setModalMode('submitting');
      rescheduleMutation.mutate({ id: rescheduleAppointmentId, startAt: selectedSlot.start });
      return;
    }

    setModalMode('submitting');
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
    const reschedule = (location.state as RescheduleState | null)?.reschedule;
    if (!reschedule || prestations.length === 0) return;

    const prestation = prestations.find((item) => item.id === reschedule.prestationId) ?? null;
    setRescheduleAppointmentId(reschedule.appointmentId);
    setSelectedPrestation(prestation);
    setSelectedDate(null);
    setSelectedSlot(null);
    setModalOpen(false);
    setStep(prestation ? 2 : 1);
    navigate(location.pathname, { replace: true });
  }, [location.pathname, location.state, navigate, prestations]);
  useEffect(() => {
    if (selectedSlot && step === 3) {
      setSubmitError(null);
      setModalMode('recap');
      setModalOpen(true);
    }
  }, [selectedSlot, step]);

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
    booking: bookingMutation.isPending || rescheduleMutation.isPending,
    modalOpen,
    setModalOpen,
    modalMode,
    setModalMode,
    submitError,
    slotsByDay,
    daySlots,
    setVisibleMonth: setMonth,
    goPrevMonth: () => setMonth(addMonths(currentMonth, -1)),
    goNextMonth: () => setMonth(addMonths(currentMonth, 1)),
    goPrevYear: () => setMonth(addMonths(currentMonth, -12)),
    goNextYear: () => setMonth(addMonths(currentMonth, 12)),
    goToday: () => setMonth(new Date()),
    handleBooking,
    currentMonth,
    isRescheduling: rescheduleAppointmentId !== null,
    resetFlow,
  };
};
