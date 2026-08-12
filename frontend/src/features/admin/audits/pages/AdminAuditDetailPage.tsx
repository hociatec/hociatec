import { useNavigate, useParams } from 'react-router';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { ErrorState, LoadingState, NotFoundState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchDateTime } from '@/shared/lib/formatters';
import type { AuditEventDto, AuditItemDto, AuditListItemDto } from '@/features/audits/publicApi';
import { useAuditMetadata } from '@/features/audits/publicApi';
import { useAdminAuditDetail } from '../hooks/useAdminAuditDetail';
import { logger } from '@/shared/lib/logger';

const AuditChecklistGroup = ({
  category,
  items,
  updateItem,
  scheduleCommentUpdate,
}: {
  category: string;
  items: AuditItemDto[];
  updateItem: (item: AuditItemDto, patch: Partial<Pick<AuditItemDto, 'isCompliant' | 'comment'>>) => Promise<void>;
  scheduleCommentUpdate: (item: AuditItemDto, comment: string) => void;
}) => {
  return (
    <div>
      <div className="mb-2 text-xs uppercase text-gray-600">{category}</div>
      <div className="space-y-2">
        {items.map((it) => (
          <div key={it.id} className="rounded border p-3">
            <div className="font-medium">
              {it.label}
              {it.level ? ` (${it.level})` : ''}
            </div>
            <div className="mt-1 flex items-center gap-3 text-sm">
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
                className="w-full rounded border p-2 text-sm"
                placeholder="Commentaire"
                value={it.comment ?? ''}
                onChange={(e) => scheduleCommentUpdate(it, e.target.value)}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

const AuditEventsList = ({ events }: { events: AuditEventDto[] }) => {
  return (
    <div>
      <div className="mb-2 font-medium">Historique</div>
      <ul className="space-y-1 text-sm text-gray-700">
        {events.map((e) => (
          <li key={e.id}>
            <span className="text-gray-500">{formatFrenchDateTime(e.createdAt)} :</span>{' '}
            {e.message || e.type}
            {e.actor?.name ? ` — ${e.actor.name}` : ''}
          </li>
        ))}
      </ul>
    </div>
  );
};

export const AdminAuditDetailPage = () => {
  const { auditId } = useParams();
  const auditTitle = auditId ? `Audit ${auditId}` : 'Audit';
  useDocumentTitle(`Admin - ${auditTitle}`);
  const navigate = useNavigate();
  const { statuses } = useAuditMetadata();
  const {
    audit,
    isValidId,
    loading,
    error,
    grouped,
    updateStatus,
    updateItem,
    scheduleCommentUpdate,
    downloadReport,
    downloadSummary,
    refresh,
  } = useAdminAuditDetail();
  const displayTitle = audit?.number ? `Audit ${audit.number}` : `Détail ${auditTitle}`;

  if (!loading && !isValidId) {
    return (
      <PageContainer size="admin" title="Audit introuvable">
        <NotFoundState>Cette fiche audit est introuvable.</NotFoundState>
      </PageContainer>
    );
  }

  return (
    <PageContainer size="admin" title={displayTitle}>
      {loading && <LoadingState>Chargement...</LoadingState>}
      {error && (
        <ErrorState
          onAction={() => void refresh()}
          actionLabel="Réessayer"
        >
          {error}
        </ErrorState>
      )}
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
                  } catch (error) {
                    logger.warn('Unable to download audit report.', { error });
                  }
                }}
              >
                Télécharger le PDF
              </button>
              <button
                className="underline"
                onClick={async () => {
                  try {
                    await downloadSummary();
                  } catch (error) {
                    logger.warn('Unable to download audit summary.', { error });
                  }
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
              <AuditChecklistGroup
                key={cat}
                category={cat}
                items={items}
                updateItem={updateItem}
                scheduleCommentUpdate={scheduleCommentUpdate}
              />
            ))}
          </div>
          {Array.isArray(audit.events) && audit.events.length > 0 && <AuditEventsList events={audit.events} />}
        </div>
      )}
      {!loading && !error && !audit && (
        <NotFoundState>Le détail de cet audit n’est pas disponible pour le moment.</NotFoundState>
      )}
    </PageContainer>
  );
};
