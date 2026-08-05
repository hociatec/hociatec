import { getHttpErrorMessage } from './httpClient';
import type { useToast } from '@/shared/components/ui/toast';

type ToastApi = ReturnType<typeof useToast>;

export const notifyMutationSuccess = (
  toast: ToastApi,
  message: string,
  options: { deletion?: boolean } = {},
) => {
  toast.show(message, {
    duration: options.deletion ? 8000 : 5000,
    variant: 'success',
  });
};

export const notifyMutationError = (
  toast: ToastApi,
  error: unknown,
  fallback: string,
) => {
  toast.show(getHttpErrorMessage(error, fallback), {
    duration: 10000,
    variant: 'error',
  });
};

export const notifyValidationError = (toast: ToastApi, message: string) => {
  toast.show(message, {
    duration: 8000,
    variant: 'error',
  });
};
