import { useEffect, useMemo, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import {
  clientDownloadAuditPdf,
  clientDownloadAuditSummaryPdf,
  fetchMyAudit,
  type AuditDetailDto,
  type AuditEventDto,
  type AuditItemDto,
} from '../api/auditsApi';
import { downloadBlob } from '@/shared/lib/downloadFile';

export const useMyAuditDetail = () => {
  const { auditId } = useParams();
  const id = Number(auditId);
  const [data, setData] = useState<(AuditDetailDto & { events: AuditEventDto[] }) | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const timer = useRef<number | null>(null);
  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    void fetchMyAudit(id)
      .then(setData)
      .catch((e) => setError(e instanceof Error ? e.message : 'Impossible de charger l’audit.'))
      .finally(() => setLoading(false));
  }, [id]);
  useEffect(() => {
    if (!id) return;
    timer.current = window.setInterval(() => {
      if (!document.hidden)
        void fetchMyAudit(id)
          .then(setData)
          .catch(() => undefined);
    }, 10000);
    return () => {
      if (timer.current) window.clearInterval(timer.current);
    };
  }, [id]);
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
  return { data, loading, error, grouped, downloadReport, downloadSummary };
};
