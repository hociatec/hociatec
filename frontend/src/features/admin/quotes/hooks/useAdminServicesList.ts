import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deleteAdminQuoteService, fetchAdminQuoteServicesPage } from '@/features/quotes/publicApi';
import type { QuoteServiceDto } from '@/features/quotes/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminQuoteQueryKeys } from '@/features/quotes/publicApi';
import type { PaginatedResult } from '@/shared/types/api';
import { normalizeSearchText } from '@/shared/lib/searchText';
export const formatServiceDuration = (service: QuoteServiceDto) =>
  !service.durationValue || !service.durationUnit
    ? '—'
    : `${service.durationValue} ${service.durationUnit === 'day' ? 'jour' : 'heure'}${service.durationValue > 1 ? 's' : ''}`;
export const useAdminServicesList = () => {
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const servicesQuery = useQuery<PaginatedResult<QuoteServiceDto>, Error>({
    queryKey: [...adminQuoteQueryKeys.services(), { page }],
    queryFn: () => fetchAdminQuoteServicesPage(page, 10),
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
  const filtered = useMemo(() => {
    const term = normalizeSearchText(search);
    return services.filter((service) => !term || normalizeSearchText(service.title).includes(term));
  }, [services, search]);
  useEffect(() => {
    setPage(1);
  }, [search]);
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
    filtered,
    pagination,
    setPage,
    handleDelete,
  };
};
