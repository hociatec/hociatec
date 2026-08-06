import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

export interface MaintenanceStatusDto {
  enabled: boolean;
  message: string;
  updatedAt?: string | null;
}

export interface SystemStatusDto {
  maintenance: MaintenanceStatusDto;
}

const unwrap = <T>(data: ApiResponse<T>, fallback: string): T => {
  if (isApiOk(data)) return data.data as T;
  throw new Error(extractApiErrorMessage(data, fallback));
};

export const fetchSystemStatus = async (): Promise<SystemStatusDto> => {
  const { data } = await httpClient.get<ApiResponse<SystemStatusDto>>('/api/public/system/status');
  return unwrap(data, 'Impossible de charger le statut du site.');
};
