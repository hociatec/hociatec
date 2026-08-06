import { BrowserRouter } from 'react-router';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { normalizeHttpError } from '@/shared/lib/httpClient';
import { AppProviders } from './providers/AppProviders';

export { AppProviders } from './providers/AppProviders';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      refetchOnReconnect: true,
      staleTime: 60_000,
      gcTime: 5 * 60_000,
      retry: (failureCount, error) => {
        const normalized = normalizeHttpError(error);
        if (
          normalized.kind === 'authentication' ||
          normalized.kind === 'authorization' ||
          normalized.kind === 'validation' ||
          normalized.kind === 'conflict' ||
          normalized.kind === 'rate_limit'
        ) {
          return false;
        }

        return failureCount < 1;
      },
    },
    mutations: {
      retry: false,
    },
  },
});

export const App = () => (
  <QueryClientProvider client={queryClient}>
    <BrowserRouter>
      <AppProviders />
    </BrowserRouter>
  </QueryClientProvider>
);
