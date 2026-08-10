import { API_BASE_URL } from '@/shared/config/appConfig';

const resolveApiOrigin = () => {
  if (API_BASE_URL && !API_BASE_URL.startsWith('/')) {
    return API_BASE_URL;
  }

  if (typeof window !== 'undefined' && window.location?.origin) {
    return `${window.location.origin}${API_BASE_URL || ''}`;
  }

  return `http://localhost${API_BASE_URL || ''}`;
};

export const resolveApiAssetUrl = (rawUrl: string | null | undefined) => {
  const normalized = rawUrl?.trim();

  if (!normalized) {
    return null;
  }

  try {
    return new URL(normalized, resolveApiOrigin()).toString();
  } catch {
    return normalized;
  }
};
