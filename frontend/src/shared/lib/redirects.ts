const TRUSTED_REDIRECT_HOSTS = new Set(['checkout.stripe.com']);

export const isTrustedRedirectUrl = (rawUrl: string): boolean => {
  try {
    const currentOrigin = typeof window === 'undefined' ? undefined : window.location.origin;
    const url = currentOrigin ? new URL(rawUrl, currentOrigin) : new URL(rawUrl);

    return url.protocol === 'https:' && (
      (typeof window !== 'undefined' && url.hostname === window.location.hostname)
      || TRUSTED_REDIRECT_HOSTS.has(url.hostname)
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
