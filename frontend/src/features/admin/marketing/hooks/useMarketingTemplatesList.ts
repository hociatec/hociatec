import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  deleteMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplatesPage,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';

export const useMarketingTemplatesList = (isTransactionalView: boolean) => {
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [query, setQuery] = useState('');
  const [scenarioFilter, setScenarioFilter] = useState('all');
  const [usageFilter, setUsageFilter] = useState(isTransactionalView ? 'transactional' : 'all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const listQuery = useQuery({
    queryKey: [...adminMarketingQueryKeys.templates(), { page }],
    queryFn: async () => {
      const [templates, segments] = await Promise.all([
        fetchMarketingTemplatesPage(page, 10),
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
  }, [query, scenarioFilter, usageFilter, statusFilter]);
  const filteredTemplates = useMemo(() => {
    const term = query.trim().toLowerCase();
    return templates.filter(
      (template) =>
        (!term ||
          [template.name, template.slug, template.subjectTemplate].some((value) =>
            value.toLowerCase().includes(term),
          )) &&
        (scenarioFilter === 'all' || template.scenarioKey === scenarioFilter) &&
        (usageFilter === 'all' ||
          (usageFilter === 'transactional' &&
            segments[template.scenarioKey]?.type === 'transactional') ||
          (usageFilter === 'campaign' &&
            segments[template.scenarioKey]?.type !== 'transactional')) &&
        (statusFilter === 'all' ||
          (statusFilter === 'active' && template.isActive) ||
          (statusFilter === 'inactive' && !template.isActive)),
    );
  }, [templates, segments, query, scenarioFilter, usageFilter, statusFilter]);
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
