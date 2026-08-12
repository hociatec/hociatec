import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';
import {
  deleteMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplatesPage,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useMarketingTemplatesList = (isTransactionalView: boolean) => {
  const [searchParams, setSearchParams] = useSearchParams();
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [query, setQuery] = useState(searchParams.get('q') ?? '');
  const [scenarioFilter, setScenarioFilter] = useState(searchParams.get('scenario') ?? 'all');
  const [usageFilter, setUsageFilter] = useState(
    searchParams.get('usage') ?? (isTransactionalView ? 'transactional' : 'all'),
  );
  const [statusFilter, setStatusFilter] = useState(searchParams.get('status') ?? 'all');
  const [page, setPage] = useState(parseNullablePositiveInteger(searchParams.get('page')) ?? 1);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const debouncedQuery = useDebounce(query.trim(), 250);
  const listQuery = useQuery({
    queryKey: [...adminMarketingQueryKeys.templates(), { page, query: debouncedQuery, scenario: scenarioFilter, usage: usageFilter, status: statusFilter }],
    queryFn: async () => {
      const [templates, segments] = await Promise.all([
        fetchMarketingTemplatesPage(
          page,
          10,
          debouncedQuery || undefined,
          scenarioFilter === 'all' ? undefined : scenarioFilter,
          usageFilter === 'all' ? undefined : usageFilter,
          statusFilter === 'all' ? undefined : statusFilter,
        ),
        fetchMarketingSegments('templates'),
      ]);
      return { templates, segments };
    },
  });
  const templates = listQuery.data?.templates.items ?? [];
  const templatesMeta = listQuery.data?.templates.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const segments = listQuery.data?.segments ?? {};
  const deleteMutation = useMutation({
    mutationFn: deleteMarketingTemplate,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminMarketingQueryKeys.templates() });
      setMessage(response.message ?? 'Le modèle d’e-mail a bien été supprimé.');
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Suppression impossible.')),
  });
  useEffect(() => {
    setUsageFilter(isTransactionalView ? 'transactional' : 'all');
  }, [isTransactionalView]);
  useEffect(() => {
    setPage(1);
  }, [debouncedQuery, scenarioFilter, usageFilter, statusFilter]);
  useEffect(() => {
    const next = new URLSearchParams();
    if (query.trim()) {
      next.set('q', query.trim());
    }
    if (scenarioFilter !== 'all') {
      next.set('scenario', scenarioFilter);
    }
    if (usageFilter !== 'all') {
      next.set('usage', usageFilter);
    }
    if (statusFilter !== 'all') {
      next.set('status', statusFilter);
    }
    if (page > 1) {
      next.set('page', String(page));
    }
    setSearchParams(next, { replace: true });
  }, [page, query, scenarioFilter, setSearchParams, statusFilter, usageFilter]);
  const filteredTemplates = useMemo(() => templates, [templates]);
  const scenarioOptions = useMemo(
    () => [
      { value: 'all', label: 'Tous les scénarios' },
      ...Object.entries(segments).map(([value, segment]) => ({
        value,
        label: segment.label,
      })),
    ],
    [segments],
  );
  const handleDelete = async (id: number) => {
    if (
      !(await confirm({
        title: 'Supprimer le modèle',
        description: 'Supprimer ce modèle ?',
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    deleteMutation.mutate(id);
  };
  return {
    templates,
    templatesMeta,
    setPage,
    segments,
    loading: listQuery.isLoading,
    query,
    setQuery,
    scenarioFilter,
    setScenarioFilter,
    usageFilter,
    setUsageFilter,
    statusFilter,
    setStatusFilter,
    error:
      error ??
      (listQuery.error ? getHttpErrorMessage(listQuery.error, 'Impossible de charger les modèles.') : null),
    message,
    filteredTemplates,
    scenarioOptions,
    handleDelete,
  };
};
