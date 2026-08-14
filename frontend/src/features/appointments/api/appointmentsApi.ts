import { getHttpErrorMessage, httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse } from '@/shared/types/api';

import type {
  AppointmentItem,
  AppointmentPayload,
  AvailabilitySlot,
  Prestation,
} from '../types/appointments';

export const fetchPrestations = async () => {
  try {
    const { data } = await httpClient.get<ApiResponse<{ items: Prestation[] }>>(
      '/api/public/appointments/prestations',
    );

    const payload = unwrapApiData(data, 'Erreur lors du chargement des prestations');
    return payload.items;
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Erreur lors du chargement des prestations'));
  }
};

export const fetchAvailability = async ({
  start,
  end,
  prestationId,
}: {
  start: string;
  end: string;
  prestationId: number;
}) => {
  try {
    const { data } = await httpClient.get<ApiResponse<{ slots: AvailabilitySlot[] }>>(
      '/api/public/appointments/availability',
      { params: { start, end, prestationId } },
    );

    const payload = unwrapApiData(data, 'Erreur lors du chargement des créneaux');
    return payload.slots;
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Erreur lors du chargement des créneaux'));
  }
};

export const bookAppointment = async (payload: AppointmentPayload) => {
  try {
    const { data } = await httpClient.post<ApiResponse<AppointmentItem>>(
      '/api/appointments',
      payload,
    );

    return unwrapApiData(data, 'Impossible de créer le rendez-vous');
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de créer le rendez-vous'));
  }
};

export const fetchMyAppointments = async () => {
  const { data } =
    await httpClient.get<ApiResponse<{ upcoming: AppointmentItem[]; past: AppointmentItem[] }>>(
      '/api/appointments/me',
    );

  return unwrapApiData(data, 'Erreur lors du chargement de mes rendez-vous');
};

export const cancelAppointment = async (id: number) => {
  const { data } = await httpClient.patch<ApiResponse<{ appointment: AppointmentItem }>>(
    `/api/appointments/${id}/status`,
    { status: 'cancelled' },
  );

  const payload = unwrapApiData(data, "Erreur lors de l'annulation du rendez-vous");
  return payload.appointment;
};

export const rescheduleAppointment = async ({ id, startAt }: { id: number; startAt: string }) => {
  const { data } = await httpClient.patch<ApiResponse<{ appointment: AppointmentItem }>>(
    `/api/appointments/${id}/reschedule`,
    { startAt },
  );

  const payload = unwrapApiData(data, 'Erreur lors du report du rendez-vous');
  return payload.appointment;
};
