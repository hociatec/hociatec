import { getHttpErrorMessage, httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

import type {
  AppointmentItem,
  AppointmentPayload,
  AvailabilitySlot,
  Prestation,
  WorkingDay,
} from './types';

const extractErrorMessage = (response: ApiResponse<unknown>, fallback: string) =>
  response.status === 'error' ? response.message : fallback;

export const fetchPrestations = async () => {
  try {
    const { data } = await httpClient.get<ApiResponse<{ items: Prestation[] }>>(
      '/api/public/appointments/prestations'
    );

    if (data.status === 'success') {
      return data.data.items;
    }

    throw new Error(extractErrorMessage(data, 'Erreur lors du chargement des prestations'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Erreur lors du chargement des prestations'));
  }
};

export const fetchSchedule = async () => {
  try {
    const { data } = await httpClient.get<ApiResponse<{ days: WorkingDay[] }>>(
      '/api/public/appointments/schedule'
    );

    if (data.status === 'success') {
      return data.data.days;
    }

    throw new Error(extractErrorMessage(data, 'Erreur lors du chargement du planning'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Erreur lors du chargement du planning'));
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
      { params: { start, end, prestationId } }
    );

    if (data.status === 'success') {
      return data.data.slots;
    }

    throw new Error(extractErrorMessage(data, 'Erreur lors du chargement des créneaux'));
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Erreur lors du chargement des créneaux'));
  }
};

export const bookAppointment = async (payload: AppointmentPayload) => {
  try {
    const { data } = await httpClient.post<ApiResponse<AppointmentItem>>(
      '/api/appointments',
      payload
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
  const { data } = await httpClient.get<
    ApiResponse<{ upcoming: AppointmentItem[]; past: AppointmentItem[] }>
  >('/api/appointments/me');

  if (data.status === 'success') {
    return data.data;
  }

  throw new Error(extractErrorMessage(data, 'Erreur lors du chargement de mes rendez-vous'));
};

export const cancelAppointment = async (id: number) => {
  const { data } = await httpClient.patch<ApiResponse<{ appointment: AppointmentItem }>>(
    `/api/appointments/${id}/status`,
    { status: 'cancelled' }
  );

  if (isApiOk(data)) {
    return data.data.appointment;
  }

  throw new Error(extractErrorMessage(data, 'Erreur lors de l\'annulation du rendez-vous'));
};
