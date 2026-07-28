import { useEffect, useState } from 'react';
import { useLocation, useParams } from 'react-router';
import {
  fetchMarketingSegments,
  fetchMarketingTemplate,
  type MarketingSegmentDefinition,
  type MarketingTemplate,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
export const useMarketingTemplateDetail = () => {
  const { templateId } = useParams();
  const location = useLocation();
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  const [template, setTemplate] = useState<MarketingTemplate | null>(null);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    if (!templateId) return;
    setLoading(true);
    setError(null);
    void Promise.all([
      fetchMarketingTemplate(Number(templateId)),
      fetchMarketingSegments(isTransactionalView ? 'transactional' : 'templates'),
    ])
      .then(([item, segmentItems]) => {
        setTemplate(item);
        setSegments(segmentItems);
      })
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger le modèle.')))
      .finally(() => setLoading(false));
  }, [templateId, isTransactionalView]);
  return { template, segments, loading, error, isTransactionalView };
};
