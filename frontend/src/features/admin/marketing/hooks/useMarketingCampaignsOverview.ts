import { useEffect, useMemo, useState } from 'react';
import {
  fetchMarketingCampaigns,
  fetchMarketingSegments,
  fetchMarketingTemplates,
  type MarketingCampaign,
  type MarketingSegmentDefinition,
  type MarketingTemplate,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
export const useMarketingCampaignsOverview = () => {
  const [templates, setTemplates] = useState<MarketingTemplate[]>([]);
  const [segments, setSegments] = useState<Record<string, MarketingSegmentDefinition>>({});
  const [campaigns, setCampaigns] = useState<MarketingCampaign[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    void Promise.all([
      fetchMarketingTemplates(),
      fetchMarketingSegments(),
      fetchMarketingCampaigns(),
    ])
      .then(([templateItems, segmentItems, campaignItems]) => {
        setTemplates(templateItems);
        setSegments(segmentItems);
        setCampaigns(campaignItems);
      })
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger le module marketing.')))
      .finally(() => setLoading(false));
  }, []);
  const activeTemplates = useMemo(
    () => templates.filter((template) => template.isActive),
    [templates],
  );
  return {
    templates,
    segments,
    campaigns,
    loading,
    error,
    activeTemplates,
    lastCampaign: campaigns[0] ?? null,
  };
};
