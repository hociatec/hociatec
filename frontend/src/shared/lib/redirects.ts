const TRUSTED_REDIRECT_HOSTS = new Set(['checkout.stripe.com']);

export const isTrustedRedirectUrl = (rawUrl: string): boolean => {
  if (typeof window === 'undefined') return false;

  try {
    const url = new URL(rawUrl, window.location.origin);
    return url.protocol === 'https:' && (
      url.hostname === window.location.hostname || TRUSTED_REDIRECT_HOSTS.has(url.hostname)
    );
  } catch {
    return false;
  }
};

export const redirectToTrustedUrl = (rawUrl: string): void => {
  if (!isTrustedRedirectUrl(rawUrl)) {
    throw new Error('URL de redirection non autorisée.');
  }

  window.location.assign(new URL(rawUrl, window.location.origin).toString());
};
