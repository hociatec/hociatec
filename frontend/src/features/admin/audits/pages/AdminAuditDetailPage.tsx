import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminFetchAudit, adminUpdateAuditItem, adminUpdateAuditStatus, adminDownloadAuditPdf, adminDownloadAuditSummaryPdf, type AuditItemDto, type AuditEventDto, type AuditListItemDto } from '@/features/audits/api';

const STATUS_LABELS: Record<AuditListItemDto['status'], string> = {
  new: 'non commencé',
  in_progress: 'en cours',
  review: 'en revue',
  done: 'finalisé',
};

const statusLabel = (s: string) => STATUS_LABELS[s as AuditListItemDto['status']] ?? s;

export const AdminAuditDetailPage = () => {
  useDocumentTitle('Admin - Audit');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const params = useParams();
  const navigate = useNavigate();
  const id = Number(params.auditId);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [audit, setAudit] = useState<any>(null);
  const pendingTimers = useRef<Record<number, ReturnType<typeof setTimeout> | undefined>>({});
  const pollTimer = useRef<number | null>(null);

  useEffect(() => {
    if (!isAdmin || !id) return;
    setLoading(true);
    setError(null);
    void adminFetchAudit(id)
      .then(setAudit)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, [id, isAdmin]);

  // Poll audit detail every 10s (avoid when typing comments)
  useEffect(() => {
    if (!isAdmin || !id) return;
    pollTimer.current = window.setInterval(() => {
      if (document.hidden) return;
      // If there are pending debounced comment updates, skip refresh to avoid overwriting the input
      const hasPending = Object.values(pendingTimers.current).some(Boolean);
      if (hasPending) return;
      void adminFetchAudit(id)
        .then(setAudit)
        .catch(() => {/* ignore background error */});
    }, 10000);
    return () => { if (pollTimer.current) window.clearInterval(pollTimer.current); };
  }, [id, isAdmin]);

  const grouped = useMemo(() => {
    if (!audit) return {} as Record<string, AuditItemDto[]>;
    const map: Record<string, AuditItemDto[]> = {};
    for (const it of (audit.items as AuditItemDto[]).sort((a, b) => a.position - b.position)) {
      map[it.category] = map[it.category] ?? [];
      map[it.category].push(it);
    }
    return map;
  }, [audit]);

  const updateStatus = async (next: string) => {
    if (!audit) return;
    setError(null);
    try {
      await adminUpdateAuditStatus(audit.id, next as any);
      setAudit((a: any) => ({ ...a, status: next }));
    } catch (e) {
      setError((e as Error).message);
    }
  };

  const updateItem = async (item: AuditItemDto, patch: Partial<Pick<AuditItemDto, 'isCompliant' | 'comment'>>) => {
    if (!audit) return;
    setError(null);
    try {
      await adminUpdateAuditItem(audit.id, item.id, patch);
      setAudit((prev: any) => ({
        ...prev,
        items: prev.items.map((x: AuditItemDto) => (x.id === item.id ? { ...x, ...patch } : x)),
      }));
    } catch (e) {
      setError((e as Error).message);
    }
  };

  // Debounced comment update to avoid a PUT on each keystroke
  const scheduleCommentUpdate = (item: AuditItemDto, comment: string) => {
    // Optimistic local update
    setAudit((prev: any) => ({
      ...prev,
      items: prev.items.map((x: AuditItemDto) => (x.id === item.id ? { ...x, comment } : x)),
    }));

    const key = item.id;
    const prevTimer = pendingTimers.current[key];
    if (prevTimer) {
      clearTimeout(prevTimer);
    }
    pendingTimers.current[key] = setTimeout(async () => {
      try {
        await adminUpdateAuditItem(audit.id, item.id, { comment });
      } catch (e) {
        setError((e as Error).message);
      }
    }, 400);
  };

  useEffect(() => {
    return () => {
      // Cleanup pending timers on unmount
      Object.values(pendingTimers.current).forEach((t) => t && clearTimeout(t));
    };
  }, []);

  if (guardLoading) {
    return (
      <PageContainer title="Audit">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }
  if (!isAdmin) {
    return (
      <PageContainer title="Audit">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer title={`Audit ${audit?.number ?? ''}`}>
      {loading && <p>Chargement…</p>}
      {error && <div className="text-red-600">{error}</div>}
      {audit && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-700">Client: {audit.client?.name} ({audit.client?.email})</div>
            <div className="space-x-2">
              <select value={audit.status} onChange={(e) => void updateStatus(e.target.value)} className="border rounded p-1 text-sm">
                <option value="new">{statusLabel('new')}</option>
                <option value="in_progress">{statusLabel('in_progress')}</option>
                <option value="review">{statusLabel('review')}</option>
                <option value="done">{statusLabel('done')}</option>
              </select>
              <button className="underline" onClick={async () => {
                try {
                  const blob = await adminDownloadAuditPdf(audit.id);
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement('a');
                  a.href = url; a.download = `${audit.number}-rapport.pdf`; a.click();
                  URL.revokeObjectURL(url);
                } catch {}
              }}>Télécharger le PDF</button>
              <button className="underline" onClick={async () => {
                try {
                  const blob = await adminDownloadAuditSummaryPdf(audit.id);
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement('a');
                  a.href = url; a.download = `${audit.number}-synthese.pdf`; a.click();
                  URL.revokeObjectURL(url);
                } catch {}
              }}>Synthèse PDF</button>
              <button className="underline" onClick={() => navigate('/admin/audits')}>Retour</button>
            </div>
          </div>
          <div>
            <div className="font-medium">Cible</div>
            <div className="text-sm">{audit.url}</div>
          </div>
          {audit.objectives && (
            <div>
              <div className="font-medium">Objectifs du client</div>
              <p className="whitespace-pre-wrap text-sm">{audit.objectives}</p>
            </div>
          )}
          <div className="space-y-6">
            {Object.entries(grouped).map(([cat, items]) => (
              <div key={cat}>
                <div className="uppercase text-xs text-gray-600 mb-2">{cat}</div>
                <div className="space-y-2">
                  {items.map((it) => (
                    <div key={it.id} className="p-3 border rounded">
                      <div className="font-medium">{it.label}{it.level ? ` (${it.level})` : ''}</div>
                      <div className="flex items-center gap-3 text-sm mt-1">
                        <label className="flex items-center gap-1">
                          <input type="radio" name={`c_${it.id}`} checked={it.isCompliant === true} onChange={() => void updateItem(it, { isCompliant: true })} /> Conforme
                        </label>
                        <label className="flex items-center gap-1">
                          <input type="radio" name={`c_${it.id}`} checked={it.isCompliant === false} onChange={() => void updateItem(it, { isCompliant: false })} /> Non conforme
                        </label>
                        <label className="flex items-center gap-1">
                          <input type="radio" name={`c_${it.id}`} checked={it.isCompliant === null} onChange={() => void updateItem(it, { isCompliant: null as any })} /> À évaluer
                        </label>
                      </div>
                      <div className="mt-2">
                        <textarea
                          className="w-full border rounded p-2 text-sm"
                          placeholder="Commentaire"
                          value={it.comment ?? ''}
                          onChange={(e) => scheduleCommentUpdate(it, e.target.value)}
                        />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
          {Array.isArray(audit.events) && audit.events.length > 0 && (
            <div>
              <div className="font-medium mb-2">Historique</div>
              <ul className="space-y-1 text-sm text-gray-700">
                {(audit.events as AuditEventDto[]).map((e) => (
                  <li key={e.id}>
                    <span className="text-gray-500">{new Date(e.createdAt).toLocaleString()}:</span>
                    {' '}{e.message || e.type}
                    {e.actor?.name ? ` — ${e.actor.name}` : ''}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </PageContainer>
  );
};
