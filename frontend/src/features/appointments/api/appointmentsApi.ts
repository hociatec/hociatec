import { getHttpErrorMessage, httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

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

    if (data.status === 'success') {
      return data.data.items;
    }

    throw new Error(extractApiErrorMessage(data, 'Erreur lors du chargement des prestations'));
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

    if (data.status === 'success') {
      return data.data.slots;
    }

    throw new Error(extractApiErrorMessage(data, 'Erreur lors du chargement des créneaux'));
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

    if (isApiOk(data)) {
      return data.data;
    }

    throw new Error(data.message || 'Impossible de créer le rendez-vous');
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Impossible de créer le rendez-vous'));
  }
};

export const fetchMyAppointments = async () => {
  const { data } =
    await httpClient.get<ApiResponse<{ upcoming: AppointmentItem[]; past: AppointmentItem[] }>>(
      '/api/appointments/me',
    );

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractApiErrorMessage(data, 'Erreur lors du chargement de mes rendez-vous'));
};

export const cancelAppointment = async (id: number) => {
  const { data } = await httpClient.patch<ApiResponse<{ appointment: AppointmentItem }>>(
    `/api/appointments/${id}/status`,
    { status: 'cancelled' },
  );

  if (isApiOk(data)) {
    return data.data.appointment;
  }

  throw new Error(extractApiErrorMessage(data, "Erreur lors de l'annulation du rendez-vous"));
};
