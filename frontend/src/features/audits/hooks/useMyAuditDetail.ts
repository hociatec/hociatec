import { useMemo } from 'react';
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

export const useMyAuditDetail = () => {
  const { auditId } = useParams();
  const id = Number(auditId);
  const detailQuery = useQuery({
    queryKey: auditQueryKeys.mineDetail(Number.isFinite(id) && id > 0 ? id : null),
    queryFn: () => fetchMyAudit(id),
    enabled: Number.isFinite(id) && id > 0,
    refetchInterval: (currentQuery) =>
      document.hidden || currentQuery.state.error || currentQuery.state.data?.status === 'done'
        ? false
        : 10_000,
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
  const downloadReport = async () => {
    if (!data) return;
    const blob = await clientDownloadAuditPdf(data.id);
    downloadBlob(blob, `${data.number}-rapport.pdf`);
  };
  const downloadSummary = async () => {
    if (!data) return;
    const blob = await clientDownloadAuditSummaryPdf(data.id);
    downloadBlob(blob, `${data.number}-synthese.pdf`);
  };
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
