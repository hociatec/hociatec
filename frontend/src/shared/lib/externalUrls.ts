const TRUSTED_WINDOW_OPEN_HOSTS = new Set(['www.facebook.com']);

export const toSafeHttpsUrl = (rawUrl: string | null | undefined, baseUrl?: string) => {
  const value = rawUrl?.trim();
  if (!value) return null;
  if (!value.startsWith('https://') && !value.startsWith('/')) return null;

  try {
    const fallbackBase =
      baseUrl && baseUrl.trim() !== ''
        ? baseUrl
        : typeof window !== 'undefined'
          ? window.location.origin
          : 'https://hociatec.fr';
    const url = new URL(value, fallbackBase);

    return url.protocol === 'https:' ? url.toString() : null;
  } catch {
    return null;
  }
};

export const toSafeAttachmentUrl = (rawUrl: string, apiBaseUrl: string) =>
  toSafeHttpsUrl(rawUrl, apiBaseUrl);

export const toSafeMailtoUrl = (
  recipientEmail: string,
  subject: string,
  body: string,
) => {
  const normalizedRecipient = recipientEmail.trim();

  if (!normalizedRecipient) return null;

  return `mailto:${encodeURIComponent(normalizedRecipient)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
};

export const openMailtoClient = (recipientEmail: string, subject: string, body: string) => {
  const safeMailto = toSafeMailtoUrl(recipientEmail, subject, body);
  if (!safeMailto) return;

  window.location.href = safeMailto;
};

export const openTrustedExternalUrl = (rawUrl: string) => {
  const safeUrl = toSafeHttpsUrl(rawUrl);
  if (!safeUrl) return;

  const url = new URL(safeUrl);
  if (!TRUSTED_WINDOW_OPEN_HOSTS.has(url.hostname)) return;

  window.open(safeUrl, '_blank', 'noopener,noreferrer');
};
