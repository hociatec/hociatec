import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { PageContainer } from '@/shared/components/PageContainer';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { adminFetchAudit, adminUpdateAuditItem, adminUpdateAuditStatus, adminDownloadAuditPdf, adminDownloadAuditSummaryPdf, type AuditItemDto } from '@/features/audits/api';

const statusLabel = (s: string) => ({
  new: 'non commencé',
  in_progress: 'en cours',
  review: 'en revue',
  done: 'finalisé',
} as const)[s as keyof any] ?? s;

export const AdminAuditDetailPage = () => {
  useDocumentTitle('Admin - Audit');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const params = useParams();
  const navigate = useNavigate();
  const id = Number(params.auditId);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [audit, setAudit] = useState<any>(null);

  useEffect(() => {
    if (!isAdmin || !id) return;
    setLoading(true);
    setError(null);
    void adminFetchAudit(id)
      .then(setAudit)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
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
                        <textarea className="w-full border rounded p-2 text-sm" placeholder="Commentaire" value={it.comment ?? ''} onChange={(e) => void updateItem(it, { comment: e.target.value })} />
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </PageContainer>
  );
};
