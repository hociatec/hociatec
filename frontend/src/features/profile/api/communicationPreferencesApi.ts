import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

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
  if (isApiOk(response)) return response.data;

  throw new Error(
    response.status === 'error'
      ? response.message
      : 'Impossible de charger les préférences de communication.',
  );
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
