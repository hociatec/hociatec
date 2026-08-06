import { useCallback, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router';
import {
  clientDownloadAuditPdf,
  clientDownloadAuditSummaryPdf,
  fetchMyAudit,
  type AuditItemDto,
} from '../api/auditsApi';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { auditQueryKeys } from '@/features/audits/queryKeys';
import { shouldRefetchWhenVisible } from '@/shared/lib/browserVisibility';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useMyAuditDetail = () => {
  const { auditId } = useParams();
  const id = parseNullablePositiveInteger(auditId);
  const isValidId = id !== null;
  const detailQuery = useQuery({
    queryKey: auditQueryKeys.mineDetail(isValidId ? id : null),
    queryFn: () => fetchMyAudit(id!),
    enabled: isValidId,
    refetchInterval: (currentQuery) =>
      shouldRefetchWhenVisible(!!currentQuery.state.error) &&
      currentQuery.state.data?.status !== 'done'
        ? 10_000
        : false,
    refetchIntervalInBackground: false,
  });
  const data = detailQuery.data ?? null;
  const grouped = useMemo(() => {
    if (!data) return {} as Record<string, AuditItemDto[]>;
    return [...data.items]
      .sort((left, right) => left.position - right.position)
      .reduce<Record<string, AuditItemDto[]>>((groups, item) => {
        (groups[item.category] ??= []).push(item);
        return groups;
      }, {});
  }, [data]);
  const downloadReport = useCallback(async () => {
    if (!data) return;
    const blob = await clientDownloadAuditPdf(data.id);
    downloadBlob(blob, `${data.number}-rapport.pdf`);
  }, [data]);
  const downloadSummary = useCallback(async () => {
    if (!data) return;
    const blob = await clientDownloadAuditSummaryPdf(data.id);
    downloadBlob(blob, `${data.number}-synthese.pdf`);
  }, [data]);
  return {
    data,
    loading: detailQuery.isLoading,
    error: detailQuery.error instanceof Error ? detailQuery.error.message : null,
    retry: detailQuery.refetch,
    grouped,
    downloadReport,
    downloadSummary,
  };
};
