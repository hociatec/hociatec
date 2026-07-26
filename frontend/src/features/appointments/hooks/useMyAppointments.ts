import { useCallback, useEffect, useState } from 'react';
import { cancelAppointment, fetchMyAppointments } from '../api/appointmentsApi';
import type { AppointmentItem } from '../types/appointments';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const useMyAppointments = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [upcoming, setUpcoming] = useState<AppointmentItem[]>([]);
  const [past, setPast] = useState<AppointmentItem[]>([]);
  const [cancellingId, setCancellingId] = useState<number | null>(null);
  const loadAppointments = useCallback(async () => {
    try {
      const data = await fetchMyAppointments();
      setUpcoming(data.upcoming);
      setPast(data.past);
      setError(null);
    } catch (reason) {
      setError(getHttpErrorMessage(reason, 'Erreur lors du chargement de mes rendez-vous'));
    } finally {
      setLoading(false);
    }
  }, []);
  useEffect(() => {
    void loadAppointments();
  }, [loadAppointments]);
  const cancel = useCallback(
    async (id: number) => {
      setCancellingId(id);
      try {
        await cancelAppointment(id);
        await loadAppointments();
      } catch (reason) {
        setError(getHttpErrorMessage(reason, "Erreur lors de l'annulation du rendez-vous"));
      } finally {
        setCancellingId(null);
      }
    },
    [loadAppointments],
  );
  return { loading, error, upcoming, past, cancellingId, cancel };
};
