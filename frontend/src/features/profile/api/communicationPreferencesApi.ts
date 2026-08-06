import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse } from '@/shared/types/api';

export interface CommunicationPreferenceChoice {
  value: string;
  label: string;
  description: string;
}

export interface CommunicationPreferencesPayload {
  preferences: string[];
  choices: CommunicationPreferenceChoice[];
}

export const fetchCommunicationPreferences = async () => {
  const { data } = await httpClient.get<ApiResponse<CommunicationPreferencesPayload>>(
    '/api/auth/communication-preferences',
  );

  return unwrapApiData(data, 'Impossible de charger les préférences de communication.');
};

export const updateCommunicationPreferences = async (preferences: string[]) => {
  const { data } = await httpClient.put<ApiResponse<CommunicationPreferencesPayload>>(
    '/api/auth/communication-preferences',
    { preferences },
  );

  return unwrapApiData(data, 'Impossible de charger les préférences de communication.');
};
