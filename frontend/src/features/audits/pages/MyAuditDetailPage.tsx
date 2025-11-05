import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { fetchMyAudit, clientDownloadAuditPdf, clientDownloadAuditSummaryPdf, type AuditDetailDto } from '../api';

const statusLabel = (s: string) => ({
  new: 'non commencé',
  in_progress: 'en cours',
  review: 'en revue',
  done: 'finalisé',
} as const)[s as keyof any] ?? s;

export const MyAuditDetailPage = () => {
  useDocumentTitle('Détail de mon audit');
  const params = useParams();
  const id = Number(params.auditId);
  const [data, setData] = useState<AuditDetailDto | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    setError(null);
    void fetchMyAudit(id)
      .then(setData)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false));
  }, [id]);

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        {loading && <p>Chargement…</p>}
        {error && <div className="text-red-600">{error}</div>}
        {data && (
          <div className="space-y-4">
            <h1 className="text-2xl font-semibold">Audit {data.number}</h1>
            <div className="text-sm text-gray-700">Statut: {statusLabel(data.status)}</div>
            <div className="text-sm text-gray-700">Cible: {data.url}</div>
            <div className="flex gap-3">
              <button className="underline text-blue-700" onClick={async () => {
                try {
                  const blob = await clientDownloadAuditPdf(data.id);
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement('a'); a.href = url; a.download = `${data.number}-rapport.pdf`; a.click(); URL.revokeObjectURL(url);
                } catch {}
              }}>Télécharger le PDF</button>
              <button className="underline text-blue-700" onClick={async () => {
                try {
                  const blob = await clientDownloadAuditSummaryPdf(data.id);
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement('a'); a.href = url; a.download = `${data.number}-synthese.pdf`; a.click(); URL.revokeObjectURL(url);
                } catch {}
              }}>Synthèse PDF</button>
            </div>
            {data.objectives && (
              <div>
                <div className="font-medium">Objectifs</div>
                <p className="whitespace-pre-wrap">{data.objectives}</p>
              </div>
            )}
            <div>
              <div className="font-medium mb-2">Critères évalués</div>
              <ul className="space-y-2">
                {data.items
                  .sort((a, b) => a.position - b.position)
                  .map((it) => (
                    <li key={it.id} className="p-3 border rounded">
                      <div className="text-sm uppercase text-gray-600">{it.category}</div>
                      <div className="font-medium">{it.label}{it.level ? ` (${it.level})` : ''}</div>
                      <div className="text-sm">Conformité: {it.isCompliant === null ? 'à évaluer' : it.isCompliant ? 'conforme' : 'non conforme'}</div>
                      {it.comment && <div className="text-sm text-gray-700">Commentaire: {it.comment}</div>}
                    </li>
                  ))}
              </ul>
            </div>
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
