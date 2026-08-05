import { useEffect, useMemo, useRef, useState, type SetStateAction } from 'react';
import { useParams } from 'react-router';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import {
  adminDownloadAuditPdf,
  adminDownloadAuditSummaryPdf,
  adminFetchAudit,
  adminUpdateAuditItem,
  adminUpdateAuditStatus,
  type AuditItemDto,
  type AuditListItemDto,
} from '@/features/audits/publicApi';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { auditQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminAuditDetail = () => {
  const { auditId } = useParams();
  const id = Number(auditId);
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const pendingTimers = useRef<Record<number, ReturnType<typeof setTimeout> | undefined>>({});
  const auditQuery = useQuery<Awaited<ReturnType<typeof adminFetchAudit>>, Error>({
    queryKey: auditQueryKeys.adminDetail(Number.isFinite(id) ? id : null),
    queryFn: () => adminFetchAudit(id),
    enabled: Number.isFinite(id) && id > 0,
    refetchInterval: () =>
      !document.hidden && !Object.values(pendingTimers.current).some(Boolean) ? 10000 : false,
  });
  const audit = auditQuery.data ?? null;
  const setAudit = (
    updater: SetStateAction<Awaited<ReturnType<typeof adminFetchAudit>> | null>,
  ) => {
    queryClient.setQueryData<Awaited<ReturnType<typeof adminFetchAudit>> | null>(
      auditQueryKeys.adminDetail(Number.isFinite(id) ? id : null),
      (current = null) => (typeof updater === 'function' ? updater(current) : updater),
    );
  };
  useEffect(
    () => () =>
      Object.values(pendingTimers.current).forEach((timer) => timer && clearTimeout(timer)),
    [],
  );
  const grouped = useMemo(
    () =>
      audit
        ? [...audit.items]
            .sort((a, b) => a.position - b.position)
            .reduce<Record<string, AuditItemDto[]>>((groups, item) => {
              (groups[item.category] ??= []).push(item);
              return groups;
            }, {})
        : {},
    [audit],
  );
  const updateStatus = async (next: AuditListItemDto['status']) => {
    if (!audit) return;
    try {
      await adminUpdateAuditStatus(audit.id, next);
      setAudit((current) => (current ? { ...current, status: next } : current));
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Impossible de mettre à jour le statut.');
    }
  };
  const updateItem = async (
    item: AuditItemDto,
    patch: Partial<Pick<AuditItemDto, 'isCompliant' | 'comment'>>,
  ) => {
    if (!audit) return;
    try {
      await adminUpdateAuditItem(audit.id, item.id, patch);
      setAudit((current) =>
        current
          ? {
              ...current,
              items: current.items.map((entry) =>
                entry.id === item.id ? { ...entry, ...patch } : entry,
              ),
            }
          : current,
      );
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Impossible de mettre à jour le point.');
    }
  };
  const scheduleCommentUpdate = (item: AuditItemDto, comment: string) => {
    if (!audit) return;
    setAudit((current) =>
      current
        ? {
            ...current,
            items: current.items.map((entry) =>
              entry.id === item.id ? { ...entry, comment } : entry,
            ),
          }
        : current,
    );
    const previous = pendingTimers.current[item.id];
    if (previous) clearTimeout(previous);
    pendingTimers.current[item.id] = setTimeout(() => {
      void adminUpdateAuditItem(audit.id, item.id, { comment }).catch((e) =>
        setError(e instanceof Error ? e.message : 'Impossible d’enregistrer le commentaire.'),
      );
    }, 400);
  };
  const downloadReport = async () => {
    if (!audit) return;
    downloadBlob(await adminDownloadAuditPdf(audit.id), `${audit.number}-rapport.pdf`);
  };
  const downloadSummary = async () => {
    if (!audit) return;
    downloadBlob(await adminDownloadAuditSummaryPdf(audit.id), `${audit.number}-synthese.pdf`);
  };
  return {
    audit,
    loading: auditQuery.isLoading,
    error: error ?? auditQuery.error?.message ?? null,
    grouped,
    updateStatus,
    updateItem,
    scheduleCommentUpdate,
    downloadReport,
    downloadSummary,
  };
};
