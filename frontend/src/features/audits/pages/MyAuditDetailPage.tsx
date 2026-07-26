import { SiteLayout } from '@/shared/components/SiteLayout';
import { ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { useMyAuditDetail } from '../hooks/useMyAuditDetail';

export const MyAuditDetailPage = () => {
  useDocumentTitle('Détail de mon audit');
  const { data, loading, error, grouped, downloadReport, downloadSummary } = useMyAuditDetail();

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        {loading && <LoadingState>Chargement de l'audit...</LoadingState>}
        {error && <ErrorState>{error}</ErrorState>}
        {data && (
          <div className="space-y-4">
            <h1 className="text-2xl font-semibold">Audit {data.number}</h1>
            <div className="text-sm text-gray-700">Statut : {data.statusLabel}</div>
            <div className="text-sm text-gray-700">Cible : {data.url}</div>
            <div className="flex gap-3">
              <button className="underline text-brand-700" onClick={() => void downloadReport()}>
                Télécharger le PDF
              </button>
              <button className="underline text-brand-700" onClick={() => void downloadSummary()}>
                Télécharger la synthèse PDF
              </button>
            </div>
            {data.objectives && (
              <div>
                <div className="font-medium">Objectifs</div>
                <p className="whitespace-pre-wrap">{data.objectives}</p>
              </div>
            )}
            <div className="space-y-6">
              {Object.entries(grouped).map(([cat, items]) => (
                <div key={cat}>
                  <div className="uppercase text-xs text-gray-600 mb-2">{cat}</div>
                  <ul className="space-y-2">
                    {items.map((it) => (
                      <li key={it.id} className="p-3 border rounded">
                        <div className="font-medium">
                          {it.label}
                          {it.level ? ` (${it.level})` : ''}
                        </div>
                        <div className="text-sm">
                          Conformité :{' '}
                          {it.isCompliant === null
                            ? 'À évaluer'
                            : it.isCompliant
                              ? 'Conforme'
                              : 'Non conforme'}
                        </div>
                        {it.comment && (
                          <div className="text-sm text-gray-700">Commentaire : {it.comment}</div>
                        )}
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
            {data.events.length > 0 && (
              <div>
                <div className="font-medium mb-2">Historique</div>
                <ul className="space-y-1 text-sm text-gray-700">
                  {data.events.map((e) => (
                    <li key={e.id}>
                      <span className="text-gray-500">
                        {formatOptionalFrenchDateTime(e.createdAt)} :
                      </span>{' '}
                      {e.message || e.type}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}
      </div>
    </SiteLayout>
  );
};
