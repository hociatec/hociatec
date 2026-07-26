import { useEffect, useMemo, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import {
  adminDownloadAuditPdf,
  adminDownloadAuditSummaryPdf,
  adminFetchAudit,
  adminUpdateAuditItem,
  adminUpdateAuditStatus,
  type AuditItemDto,
  type AuditListItemDto,
} from '@/features/audits/api/auditsApi';
import { downloadBlob } from '@/shared/lib/downloadFile';

export const useAdminAuditDetail = () => {
  const { auditId } = useParams();
  const id = Number(auditId);
  const [audit, setAudit] = useState<Awaited<ReturnType<typeof adminFetchAudit>> | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pendingTimers = useRef<Record<number, ReturnType<typeof setTimeout> | undefined>>({});
  const pollTimer = useRef<number | null>(null);
  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    void adminFetchAudit(id)
      .then(setAudit)
      .catch((e) => setError(e instanceof Error ? e.message : 'Impossible de charger l’audit.'))
      .finally(() => setLoading(false));
  }, [id]);
  useEffect(() => {
    if (!id) return;
    pollTimer.current = window.setInterval(() => {
      if (!document.hidden && !Object.values(pendingTimers.current).some(Boolean))
        void adminFetchAudit(id)
          .then(setAudit)
          .catch(() => undefined);
    }, 10000);
    return () => {
      if (pollTimer.current) window.clearInterval(pollTimer.current);
    };
  }, [id]);
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
    loading,
    error,
    grouped,
    updateStatus,
    updateItem,
    scheduleCommentUpdate,
    downloadReport,
    downloadSummary,
  };
};
