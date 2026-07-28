import { API_BASE_URL } from '@/shared/config/appConfig';

export const getRequestPath = (url?: string) => {
  if (!url) return '';
  const baseUrl =
    API_BASE_URL && !API_BASE_URL.startsWith('/')
      ? API_BASE_URL
      : `http://localhost${API_BASE_URL || ''}`;

  return new URL(url, baseUrl).pathname;
};
