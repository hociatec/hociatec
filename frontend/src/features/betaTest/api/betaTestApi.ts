import { httpClient } from '@/shared/lib/httpClient';
import type { ApiResponse } from '@/shared/types/api';

export interface BetaTesterPayload {
  firstName: string;
  lastName: string;
  email: string;
  computerLevel: string;
  visualProfile: string;
  assistiveTechnology: string;
  motivation: string;
  consent: boolean;
  website?: string;
}

export const submitBetaTester = async (payload: BetaTesterPayload): Promise<string> => {
  const { data } = await httpClient.post<ApiResponse<null>>('/api/public/beta-testers', payload);
  if (data.status === 'error') {
    const error = new Error(data.message);
    (error as Error & { details?: string[] }).details = data.details;
    throw error;
  }

  return data.message ?? 'Votre demande a bien été enregistrée.';
};
