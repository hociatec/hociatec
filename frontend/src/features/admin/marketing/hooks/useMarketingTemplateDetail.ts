import { useMemo } from 'react';
import { useLocation, useParams } from 'react-router';
import { useQuery } from '@tanstack/react-query';
import {
  fetchMarketingSegments,
  fetchMarketingTemplate,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminMarketingQueryKeys } from '@/features/admin/marketing/queryKeys';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useMarketingTemplateDetail = () => {
  const { templateId } = useParams();
  const location = useLocation();
  const parsedTemplateId = parseNullablePositiveInteger(templateId);
  const isTransactionalView = location.pathname.startsWith('/admin/transactional-emails');
  const segmentType = isTransactionalView ? 'transactional' : 'templates';
  const detailQuery = useQuery({
    queryKey: adminMarketingQueryKeys.templateDetail(
      parsedTemplateId !== null ? parsedTemplateId : null,
      segmentType,
    ),
    queryFn: async () => {
      const [template, segments] = await Promise.all([
        fetchMarketingTemplate(parsedTemplateId || 0),
        fetchMarketingSegments(segmentType),
      ]);
      return { template, segments };
    },
    enabled: parsedTemplateId !== null,
  });
  return useMemo(
    () => ({
      template: detailQuery.data?.template ?? null,
      segments: detailQuery.data?.segments ?? {},
      loading: detailQuery.isLoading,
      error: detailQuery.error
        ? getHttpErrorMessage(detailQuery.error, 'Impossible de charger le modèle.')
        : null,
      isTransactionalView,
    }),
    [detailQuery.data, detailQuery.error, detailQuery.isLoading, isTransactionalView],
  );
};
