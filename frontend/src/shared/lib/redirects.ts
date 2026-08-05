const TRUSTED_REDIRECT_HOSTS = new Set(['checkout.stripe.com']);
const UNSAFE_INTERNAL_REDIRECT_PATHS = new Set(['/login', '/register', '/forgot-password']);

export const isSafeInternalRedirectPath = (path?: string | null): path is string =>
  Boolean(
    path &&
      path.startsWith('/') &&
      !path.startsWith('//') &&
      !UNSAFE_INTERNAL_REDIRECT_PATHS.has(path),
  );

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
