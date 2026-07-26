import { isAxiosError } from 'axios';

export const getHttpErrorMessage = (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
) => {
  if (!isAxiosError(error)) {
    return error instanceof Error && error.message ? error.message : fallback;
  }

  const responseData = error.response?.data as { message?: unknown } | undefined;
  const apiMessage = typeof responseData?.message === 'string' ? responseData.message.trim() : '';
  if (apiMessage) return apiMessage;
  if (!error.response) return 'Le service est momentanément indisponible. Vérifiez que le serveur API est démarré, puis réessayez.';
  if (error.response.status >= 500) return 'Le service rencontre un problème temporaire. Veuillez réessayer dans quelques instants.';
  if (error.response.status === 404) return 'La ressource demandée est introuvable.';
  if (error.response.status === 401 || error.response.status === 403) return 'Vous devez être connecté avec les droits nécessaires pour accéder à cette ressource.';
  return fallback;
};

export const getHttpErrorMessageAsync = async (
  error: unknown,
  fallback = 'Une erreur est survenue. Veuillez réessayer dans quelques instants.',
) => {
  if (!isAxiosError(error) || !(error.response?.data instanceof Blob)) {
    return getHttpErrorMessage(error, fallback);
  }

  try {
    const payload = JSON.parse(await error.response.data.text()) as {
      message?: unknown;
      error?: unknown;
      data?: { message?: unknown };
    };
    const apiMessage = payload.message ?? payload.error ?? payload.data?.message;
    if (typeof apiMessage === 'string' && apiMessage.trim() !== '') return apiMessage.trim();
  } catch {
    // Le message HTTP standard reste le fallback.
  }

  return getHttpErrorMessage(error, fallback);
};
