import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
import { type ApiResponse } from '@/shared/types/api';

export interface MaintenanceStatusDto {
  enabled: boolean;
  message: string;
  updatedAt?: string | null;
}

export interface SystemStatusDto {
  maintenance: MaintenanceStatusDto;
}

export const fetchSystemStatus = async (): Promise<SystemStatusDto> => {
  const { data } = await httpClient.get<ApiResponse<SystemStatusDto>>('/api/public/system/status');
  return unwrapApiData(data, 'Impossible de charger le statut du site.');
};
