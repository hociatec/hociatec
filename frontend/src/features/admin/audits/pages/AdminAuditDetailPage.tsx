import { useNavigate } from 'react-router';
import { PageContainer } from '@/shared/components/PageContainer';
import { LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchDateTime } from '@/shared/lib/formatters';
import type { AuditListItemDto } from '@/features/audits/api/auditsApi';
import { useAuditMetadata } from '@/features/audits/hooks/useAuditMetadata';
import { useAdminAuditDetail } from '../hooks/useAdminAuditDetail';

export const AdminAuditDetailPage = () => {
  useDocumentTitle('Admin - Audit');
  const navigate = useNavigate();
  const { statuses } = useAuditMetadata();
  const {
    audit,
    loading,
    error,
    grouped,
    updateStatus,
    updateItem,
    scheduleCommentUpdate,
    downloadReport,
    downloadSummary,
  } = useAdminAuditDetail();

  return (
    <PageContainer size="admin" title={`Audit ${audit?.number ?? ''}`}>
      {loading && <LoadingState>Chargement...</LoadingState>}
      {error && <div className="text-red-600">{error}</div>}
      {audit && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="text-sm text-gray-700">
              Client : {audit.client?.name} ({audit.client?.email})
            </div>
            <div className="space-x-2">
              <select
                value={audit.status}
                onChange={(e) => void updateStatus(e.target.value as AuditListItemDto['status'])}
                className="border rounded p-1 text-sm"
              >
                {statuses.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </select>
              <button
                className="underline"
                onClick={async () => {
                  try {
                    await downloadReport();
                  } catch {}
                }}
              >
                Télécharger le PDF
              </button>
              <button
                className="underline"
                onClick={async () => {
                  try {
                    await downloadSummary();
                  } catch {}
                }}
              >
                Télécharger la synthèse PDF
              </button>
              <button className="underline" onClick={() => navigate('/admin/audits')}>
                Retour
              </button>
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
                      <div className="font-medium">
                        {it.label}
                        {it.level ? ` (${it.level})` : ''}
                      </div>
                      <div className="flex items-center gap-3 text-sm mt-1">
                        <label className="flex items-center gap-1">
                          <input
                            type="radio"
                            name={`c_${it.id}`}
                            checked={it.isCompliant === true}
                            onChange={() => void updateItem(it, { isCompliant: true })}
                          />{' '}
                          Conforme
                        </label>
                        <label className="flex items-center gap-1">
                          <input
                            type="radio"
                            name={`c_${it.id}`}
                            checked={it.isCompliant === false}
                            onChange={() => void updateItem(it, { isCompliant: false })}
                          />{' '}
                          Non conforme
                        </label>
                        <label className="flex items-center gap-1">
                          <input
                            type="radio"
                            name={`c_${it.id}`}
                            checked={it.isCompliant === null}
                            onChange={() => void updateItem(it, { isCompliant: null })}
                          />{' '}
                          À évaluer
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
                {audit.events.map((e) => (
                  <li key={e.id}>
                    <span className="text-gray-500">{formatFrenchDateTime(e.createdAt)} :</span>{' '}
                    {e.message || e.type}
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
