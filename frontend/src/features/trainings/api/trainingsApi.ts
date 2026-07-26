import { httpClient, getHttpErrorMessage } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';

export type TrainingFormat = 'onsite' | 'remote';
export type TrainingEnrollmentStatus =
  'pending_payment' | 'paid' | 'confirmed' | 'completed' | 'cancelled';

export const FALLBACK_TRAINING_CATEGORIES = [
  { value: 'bases', label: 'Bases numériques' },
  { value: 'securite', label: 'Sécurité et sauvegarde' },
  { value: 'productivite', label: 'Productivité' },
  { value: 'web', label: 'Web et présence en ligne' },
  { value: 'ia', label: 'Intelligence artificielle' },
  { value: 'entreprise', label: 'Entreprise' },
  { value: 'general', label: 'Général' },
] as const;

export interface TrainingCategoryDto {
  id: number;
  name: string;
  slug: string;
  position: number;
  isActive: boolean;
}

export interface TrainingCategoryInput {
  name: string;
  slug?: string;
  position: number;
  isActive: boolean;
}

export interface TrainingRoadmapItemDto {
  id: number;
  position: number;
  title: string;
}

export interface TrainingDto {
  id: number;
  title: string;
  slug: string;
  shortDescription: string | null;
  objective: string | null;
  audience: string | null;
  category: string;
  durationMinutes: number;
  priceCents: number;
  availableFormats: TrainingFormat[];
  isActive: boolean;
  roadmap: TrainingRoadmapItemDto[];
}

export interface TrainingSessionDto {
  id: number;
  training: TrainingDto;
  format: TrainingFormat;
  startsAt: string;
  endsAt: string;
  dailyStartTime: string;
  dailyEndTime: string;
  includeWeekends: boolean;
  location: string | null;
  meetingUrl: string | null;
  capacity: number;
  enrolledCount: number;
  remainingSeats: number;
  status: string;
}

export interface TrainingEnrollmentDto {
  id: number;
  status: TrainingEnrollmentStatus | string;
  priceCents: number;
  scheduledStartsAt: string;
  scheduledEndsAt: string;
  paidAt: string | null;
  stripeSessionId?: string | null;
  checkoutUrl?: string | null;
  createdAt: string;
  session: TrainingSessionDto;
}

export interface TrainingInput {
  title: string;
  slug?: string;
  shortDescription?: string | null;
  objective?: string | null;
  audience?: string | null;
  category: string;
  durationMinutes: number;
  priceCents: number;
  availableFormats: TrainingFormat[];
  isActive: boolean;
  roadmap: string[];
}

export interface TrainingSessionInput {
  trainingId: number;
  format: TrainingFormat;
  startsAt: string;
  endsAt: string;
  dailyStartTime: string;
  dailyEndTime: string;
  includeWeekends: boolean;
  location?: string | null;
  meetingUrl?: string | null;
  capacity: number;
  status: string;
}

const unwrapData = <T>(response: ApiResponse<T>): T => {
  if (response.status === 'error') {
    throw new Error(response.message);
  }

  return response.data;
};

export const formatTrainingFormat = (format?: string | null) =>
  format === 'remote' ? 'Distanciel' : format === 'onsite' ? 'Présentiel' : '-';

export const formatTrainingCategory = (category?: string | null) =>
  FALLBACK_TRAINING_CATEGORIES.find((item) => item.value === category)?.label ??
  category ??
  'Général';

export const formatTrainingEnrollmentStatus = (status?: string | null) => {
  const labels: Record<string, string> = {
    pending_payment: 'Paiement en attente',
    paid: 'Payée',
    confirmed: 'Confirmée',
    completed: 'Terminée',
    cancelled: 'Annulée',
  };

  return status ? (labels[status] ?? status) : '-';
};

export const fetchPublicTrainings = async (category?: string): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>('/api/public/trainings', {
    params: category ? { category } : undefined,
  });
  return unwrapData(res.data).items;
};

export const fetchPublicTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    '/api/public/training-categories',
  );
  return unwrapData(res.data).items;
};

export const fetchPublicTraining = async (
  slug: string,
): Promise<{ training: TrainingDto; sessions: TrainingSessionDto[] }> => {
  const res = await httpClient.get<
    ApiResponse<{ training: TrainingDto; sessions: TrainingSessionDto[] }>
  >(`/api/public/trainings/${encodeURIComponent(slug)}`);
  return unwrapData(res.data);
};

export const enrollTrainingSession = async (
  sessionId: number,
  startsAt: string,
): Promise<TrainingEnrollmentDto> => {
  try {
    const res = await httpClient.post<ApiResponse<TrainingEnrollmentDto>>(
      '/api/trainings/enrollments',
      { sessionId, startsAt },
    );
    return unwrapData(res.data);
  } catch (error) {
    throw new Error(getHttpErrorMessage(error, 'Inscription impossible.'));
  }
};

export const fetchMyTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
    '/api/trainings/enrollments/me',
  );
  return unwrapData(res.data).items;
};

export const fetchAdminTrainings = async (): Promise<TrainingDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingDto[] }>>('/api/admin/trainings');
  return unwrapData(res.data).items;
};

export const fetchAdminTrainingCategories = async (): Promise<TrainingCategoryDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingCategoryDto[] }>>(
    '/api/admin/training-categories',
  );
  return unwrapData(res.data).items;
};

export const saveAdminTrainingCategory = async (
  payload: TrainingCategoryInput,
  id?: number,
): Promise<TrainingCategoryDto> => {
  const res = id
    ? await httpClient.post<ApiResponse<TrainingCategoryDto>>(
        `/api/admin/training-categories/${id}`,
        payload,
      )
    : await httpClient.post<ApiResponse<TrainingCategoryDto>>(
        '/api/admin/training-categories',
        payload,
      );

  return unwrapData(res.data);
};

export const deleteAdminTrainingCategory = async (id: number): Promise<void> => {
  await httpClient.delete(`/api/admin/training-categories/${id}`);
};

export const fetchAdminTraining = async (id: number): Promise<TrainingDto> => {
  const res = await httpClient.get<ApiResponse<TrainingDto>>(`/api/admin/trainings/${id}`);
  return unwrapData(res.data);
};

export const saveAdminTraining = async (
  payload: TrainingInput,
  id?: number,
): Promise<TrainingDto> => {
  const res = id
    ? await httpClient.post<ApiResponse<TrainingDto>>(`/api/admin/trainings/${id}`, payload)
    : await httpClient.post<ApiResponse<TrainingDto>>('/api/admin/trainings', payload);

  return unwrapData(res.data);
};

export const deleteAdminTraining = async (id: number): Promise<void> => {
  await httpClient.delete(`/api/admin/trainings/${id}`);
};

export const fetchAdminTrainingSessions = async (): Promise<TrainingSessionDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingSessionDto[] }>>(
    '/api/admin/training-sessions',
  );
  return unwrapData(res.data).items;
};

export const saveAdminTrainingSession = async (
  payload: TrainingSessionInput,
  id?: number,
): Promise<TrainingSessionDto> => {
  const res = id
    ? await httpClient.post<ApiResponse<TrainingSessionDto>>(
        `/api/admin/training-sessions/${id}`,
        payload,
      )
    : await httpClient.post<ApiResponse<TrainingSessionDto>>(
        '/api/admin/training-sessions',
        payload,
      );

  return unwrapData(res.data);
};

export const deleteAdminTrainingSession = async (id: number): Promise<void> => {
  await httpClient.delete(`/api/admin/training-sessions/${id}`);
};

export const fetchAdminTrainingEnrollments = async (): Promise<TrainingEnrollmentDto[]> => {
  const res = await httpClient.get<ApiResponse<{ items: TrainingEnrollmentDto[] }>>(
    '/api/admin/training-enrollments',
  );
  return unwrapData(res.data).items;
};

export const updateAdminTrainingEnrollmentStatus = async (
  id: number,
  status: TrainingEnrollmentStatus,
): Promise<TrainingEnrollmentDto> => {
  const res = await httpClient.patch<ApiResponse<TrainingEnrollmentDto>>(
    `/api/admin/training-enrollments/${id}/status`,
    { status },
  );
  return unwrapData(res.data);
};
