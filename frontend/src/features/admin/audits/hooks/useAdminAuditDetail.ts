import { useCallback, useEffect, useMemo, useRef, useState, type SetStateAction } from 'react';
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
import { shouldRefetchWhenVisible } from '@/shared/lib/browserVisibility';
import { auditQueryKeys } from '@/features/audits/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useAdminAuditDetail = () => {
  const { auditId } = useParams();
  const id = parseNullablePositiveInteger(auditId);
  const isValidId = id !== null;
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const pendingTimers = useRef<Record<number, ReturnType<typeof setTimeout> | undefined>>({});
  const clearTimer = useCallback((itemId: number) => {
    const timeoutId = pendingTimers.current[itemId];
    if (timeoutId === undefined) {
      return;
    }

    clearTimeout(timeoutId);
    delete pendingTimers.current[itemId];
  }, []);
  const clearAllTimers = useCallback(() => {
    Object.keys(pendingTimers.current).forEach((itemId) => {
      const parsedItemId = parseNullablePositiveInteger(itemId);
      if (parsedItemId !== null) {
        clearTimer(parsedItemId);
      }
    });
  }, [clearTimer]);

  const auditQuery = useQuery<Awaited<ReturnType<typeof adminFetchAudit>>, Error>({
    queryKey: auditQueryKeys.adminDetail(isValidId ? id : null),
    queryFn: () => adminFetchAudit(id!),
    enabled: isValidId,
    refetchInterval: (currentQuery) =>
      shouldRefetchWhenVisible(!!currentQuery.state.error) &&
      currentQuery.state.data?.status !== 'done' &&
      !Object.values(pendingTimers.current).some(Boolean)
        ? 10000
        : false,
  });
  const audit = auditQuery.data ?? null;
  const setAudit = useCallback(
    (updater: SetStateAction<Awaited<ReturnType<typeof adminFetchAudit>> | null>) => {
      queryClient.setQueryData<Awaited<ReturnType<typeof adminFetchAudit>> | null>(
        auditQueryKeys.adminDetail(isValidId ? id : null),
        (current = null) => (typeof updater === 'function' ? updater(current) : updater),
      );
    },
    [queryClient, isValidId, id],
  );
  useEffect(
    () => () => clearAllTimers(),
    [clearAllTimers],
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
  const updateStatus = useCallback(
    async (next: AuditListItemDto['status']) => {
      if (!audit) return;
      try {
        await adminUpdateAuditStatus(audit.id, next);
        setAudit((current) => (current ? { ...current, status: next } : current));
      } catch (e) {
        setError(getHttpErrorMessage(e, 'Impossible de mettre à jour le statut.'));
      }
    },
    [audit, setAudit, setError],
  );
  const updateItem = useCallback(
    async (
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
        setError(getHttpErrorMessage(e, 'Impossible de mettre à jour le point.'));
      }
    },
    [audit, setAudit, setError],
  );
  const scheduleCommentUpdate = useCallback((item: AuditItemDto, comment: string) => {
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
    clearTimer(item.id);

    const timer = setTimeout(() => {
      void adminUpdateAuditItem(audit.id, item.id, { comment })
        .catch((e) => setError(getHttpErrorMessage(e, 'Impossible d’enregistrer le commentaire.')))
        .finally(() => clearTimer(item.id));
    }, 400);
    pendingTimers.current[item.id] = timer;
  }, [audit, clearTimer, setAudit, setError]);
  const downloadReport = useCallback(async () => {
    if (!audit) return;
    downloadBlob(await adminDownloadAuditPdf(audit.id), `${audit.number}-rapport.pdf`);
  }, [audit]);
  const downloadSummary = useCallback(async () => {
    if (!audit) return;
    downloadBlob(await adminDownloadAuditSummaryPdf(audit.id), `${audit.number}-synthese.pdf`);
  }, [audit]);
  return {
    audit,
    isValidId,
    loading: auditQuery.isLoading,
    error: error ?? auditQuery.error?.message ?? null,
    grouped,
    updateStatus,
    updateItem,
    scheduleCommentUpdate,
    downloadReport,
    downloadSummary,
    refresh: auditQuery.refetch,
  };
};
