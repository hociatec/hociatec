import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import { deleteAdminQuoteService, fetchAdminQuoteServicesPage } from '@/features/quotes/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminQuoteQueryKeys } from '@/features/quotes/publicApi';
import type { PaginatedResult } from '@/shared/types/api';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';
export const formatServiceDuration = (service: QuoteServiceDto) =>
  !service.durationValue || !service.durationUnit
    ? '—'
    : `${service.durationValue} ${service.durationUnit === 'day' ? 'jour' : 'heure'}${service.durationValue > 1 ? 's' : ''}`;
export const useAdminServicesList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState(searchParams.get('q') ?? '');
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const debouncedSearch = useDebounce(search.trim(), 250);
  const servicesQuery = useQuery<PaginatedResult<QuoteServiceDto>, Error>({
    queryKey: [...adminQuoteQueryKeys.services(), { page, q: debouncedSearch }],
    queryFn: () => fetchAdminQuoteServicesPage(page, 10, debouncedSearch || undefined),
  });
  const services = servicesQuery.data?.items ?? [];
  const pagination = servicesQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const deleteMutation = useMutation({
    mutationFn: deleteAdminQuoteService,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminQuoteQueryKeys.services() });
      setMessage(response.message ?? 'Le service a bien été supprimé.');
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Suppression impossible.')),
  });
  useEffect(() => {
    setPage(1);
  }, [debouncedSearch]);
  useEffect(() => {
    const next = new URLSearchParams();
    if (search.trim()) {
      next.set('q', search.trim());
    }
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, search, setSearchParams]);
  const handleDelete = async (id: number) => {
    const service = services.find((item) => item.id === id);
    if (
      !(await confirm({
        title: 'Supprimer le service',
        description: `Supprimer ${service ? `"${service.title}"` : 'ce service'} ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    deleteMutation.mutate(id);
  };
  return {
    loading: servicesQuery.isLoading,
    error:
      error ??
      (servicesQuery.error ? getHttpErrorMessage(servicesQuery.error, 'Chargement impossible.') : null),
    message,
    search,
    setSearch,
    filtered: services,
    pagination,
    setPage,
    handleDelete,
  };
};
