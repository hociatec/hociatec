import axios from 'axios';

import { httpClient } from '@/shared/lib/httpClient';
import { extractApiErrorMessage } from '@/shared/lib/apiResponses';
import type { MaintenanceStatusDto } from '@/shared/api/systemStatus';
import { isApiOk, type ApiMutationResult, type ApiResponse } from '@/shared/types/api';

export interface BackupSettingsDto {
  enabled: boolean;
  intervalHours: number;
  retentionCount: number;
  lastSuccessfulRunAt?: string | null;
  nextRunAt?: string | null;
}

export interface BackupFileDto {
  filename: string;
  sizeBytes: number;
  createdAt: string;
}

export interface BackupRunDto {
  id: string;
  status: 'running' | 'success' | 'failed';
  trigger: 'manual' | 'scheduled' | string;
  filename?: string | null;
  startedAt: string;
  finishedAt?: string | null;
  sizeBytes?: number | null;
  message: string;
}

export interface BackupStatusDto {
  settings: BackupSettingsDto;
  backups: BackupFileDto[];
  history: BackupRunDto[];
  maintenance: MaintenanceStatusDto;
  tools: {
    mysqldumpAvailable: boolean;
    gzipAvailable: boolean;
  };
  scheduler: {
    command: string;
    cronExample: string;
  };
}

const unwrap = <T>(data: ApiResponse<T>, fallback: string): T => {
  if (isApiOk(data)) return data.data as T;
  throw new Error(extractApiErrorMessage(data, fallback));
};

const rethrowApiError = (error: unknown, fallback: string): never => {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as ApiResponse<unknown> | undefined;
    if (data) {
      throw new Error(extractApiErrorMessage(data, fallback));
    }
  }

  throw new Error(error instanceof Error ? error.message : fallback);
};

export const fetchBackupStatus = async (): Promise<BackupStatusDto> => {
  const { data } = await httpClient.get<ApiResponse<BackupStatusDto>>('/api/admin/backups');
  return unwrap(data, 'Impossible de charger les sauvegardes.');
};

export const updateBackupSettings = async (
  payload: Partial<Pick<BackupSettingsDto, 'enabled' | 'intervalHours' | 'retentionCount'>>,
): Promise<ApiMutationResult<BackupStatusDto>> => {
  try {
    const { data } = await httpClient.patch<ApiResponse<BackupStatusDto>>(
      '/api/admin/backups/settings',
      payload,
    );
    return { data: unwrap(data, 'Impossible de sauvegarder la configuration.'), message: data.message };
  } catch (error) {
    return rethrowApiError(error, 'Impossible de sauvegarder la configuration.');
  }
};

export const runBackupNow = async (): Promise<ApiMutationResult<BackupStatusDto>> => {
  try {
    const { data } = await httpClient.post<ApiResponse<BackupStatusDto>>(
      '/api/admin/backups/run',
      {},
    );
    return { data: unwrap(data, 'Impossible de lancer la sauvegarde.'), message: data.message };
  } catch (error) {
    return rethrowApiError(error, 'Impossible de lancer la sauvegarde.');
  }
};

export const updateMaintenanceMode = async (payload: {
  enabled: boolean;
  message: string;
}): Promise<ApiMutationResult<MaintenanceStatusDto>> => {
  try {
    const { data } = await httpClient.post<ApiResponse<{ maintenance: MaintenanceStatusDto }>>(
      '/api/admin/backups/maintenance',
      payload,
    );
    return { data: unwrap(data, 'Impossible de modifier le mode maintenance.').maintenance, message: data.message };
  } catch (error) {
    return rethrowApiError(error, 'Impossible de modifier le mode maintenance.');
  }
};
