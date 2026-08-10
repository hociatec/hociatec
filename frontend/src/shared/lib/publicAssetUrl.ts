const PUBLIC_BASE_URL = (import.meta.env.BASE_URL || '/').replace(/\/+$/, '/');

export const resolvePublicAssetUrl = (rawPath: string) => {
  const normalized = rawPath.trim();

  if (
    normalized === '' ||
    normalized.startsWith('http://') ||
    normalized.startsWith('https://') ||
    normalized.startsWith('data:')
  ) {
    return normalized;
  }

  return `${PUBLIC_BASE_URL}${normalized.replace(/^\/+/, '')}`;
};
