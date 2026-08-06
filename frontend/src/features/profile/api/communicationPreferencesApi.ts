import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/apiResponses';
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

const unwrap = (response: ApiResponse<CommunicationPreferencesPayload>) => {
  return unwrapApiData(response, 'Impossible de charger les préférences de communication.');
};

export const fetchCommunicationPreferences = async () => {
  const { data } = await httpClient.get<ApiResponse<CommunicationPreferencesPayload>>(
    '/api/auth/communication-preferences',
  );

  return unwrap(data);
};

export const updateCommunicationPreferences = async (preferences: string[]) => {
  const { data } = await httpClient.put<ApiResponse<CommunicationPreferencesPayload>>(
    '/api/auth/communication-preferences',
    { preferences },
  );

  return unwrap(data);
};
