import { useEffect, useMemo, useState } from 'react';
import {
  deleteMarketingTemplate,
  fetchMarketingSegments,
  fetchMarketingTemplates,
  type MarketingSegmentDefinition,
  type MarketingTemplate,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
export const useMarketingTemplatesList = (isTransactionalView: boolean) => {
  const confirm = useConfirm();
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState('');
  const [scenarioFilter, setScenarioFilter] = useState('all');
  const [usageFilter, setUsageFilter] = useState(isTransactionalView ? 'transactional' : 'all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  useEffect(() => {
    void Promise.all([fetchMarketingTemplates(), fetchMarketingSegments('templates')])
      .then(([items, segmentItems]) => {
        setTemplates(items);
        setSegments(segmentItems);
      })
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger les modèles.')))
      .finally(() => setLoading(false));
  }, []);
  useEffect(() => {
    setUsageFilter(isTransactionalView ? 'transactional' : 'all');
  }, [isTransactionalView]);
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
    try {
      await deleteMarketingTemplate(id);
      setTemplates((items) => items.filter((item) => item.id !== id));
      setMessage('Modèle supprimé.');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Suppression impossible.'));
    }
  };
  return {
    templates,
    segments,
    loading,
    query,
    setQuery,
    scenarioFilter,
    setScenarioFilter,
    usageFilter,
    setUsageFilter,
    statusFilter,
    setStatusFilter,
    error,
    message,
    filteredTemplates,
    scenarioOptions,
    handleDelete,
  };
};
