import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { cancelAppointment, fetchMyAppointments } from '../api/appointmentsApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { appointmentQueryKeys } from '@/shared/lib/queryKeys';

export const useMyAppointments = () => {
  const [cancellingId, setCancellingId] = useState<number | null>(null);
  const queryClient = useQueryClient();
  const appointmentsQuery = useQuery({
    queryKey: appointmentQueryKeys.mine(),
    queryFn: fetchMyAppointments,
  });
  const cancelMutation = useMutation({
    mutationFn: cancelAppointment,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: appointmentQueryKeys.mine() });
    },
    onSettled: () => setCancellingId(null),
  });
  const cancel = (id: number) => {
    setCancellingId(id);
    cancelMutation.mutate(id);
  };
  const error = appointmentsQuery.error
    ? getHttpErrorMessage(appointmentsQuery.error, 'Erreur lors du chargement de mes rendez-vous')
    : cancelMutation.error
      ? getHttpErrorMessage(cancelMutation.error, "Erreur lors de l'annulation du rendez-vous")
      : null;

  return {
    loading: appointmentsQuery.isLoading,
    error,
    upcoming: appointmentsQuery.data?.upcoming ?? [],
    past: appointmentsQuery.data?.past ?? [],
    cancellingId,
    cancel,
  };
};
