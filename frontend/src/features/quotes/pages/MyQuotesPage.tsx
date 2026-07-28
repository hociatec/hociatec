import { Link } from 'react-router';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { ConfirmDialog } from '@/shared/components/feedback/ConfirmDialog';
import { EmptyState, FeedbackMessage, StableContent } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { useMyQuotes } from '../hooks/useMyQuotes';

export const MyQuotesPage = () => {
  useDocumentTitle('Mes devis');

  const {
    items,
    loading,
    error,
    downloadingId,
    confirmOpen,
    requestDelete,
    cancelDelete,
    confirmDelete,
    download,
  } = useMyQuotes();

  return (
    <SiteLayout>
      <PageContainer size="medium" title="Mes devis">
        <StableContent
          loading={loading}
          hasContent={items.length > 0 || !loading}
          loadingLabel="Chargement..."
        >
          {error ? (
            <FeedbackMessage>{error}</FeedbackMessage>
          ) : items.length === 0 ? (
            <EmptyState>Aucun devis.</EmptyState>
          ) : (
            <AdminTableShell>
              <table className="catalog-admin-table">
                <thead>
                  <tr>
                    <th>Numéro</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Total TTC</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((q) => {
                    const isDownloading = downloadingId === q.id;

                    return (
                      <tr key={q.id}>
                        <td>{q.number}</td>
                        <td>{q.statusLabel}</td>
                        <td>{formatOptionalFrenchDate(q.createdAt)}</td>
                        <td>{formatEuroCents(q?.totals?.ttc ?? 0)}</td>
                        <td>
                          <div className="catalog-admin-actions">
                            <Link to={`/quotes/me/${q.id}`} className="catalog-admin-actions__edit">
                              Consulter
                            </Link>
                            <button
                              type="button"
                              className="register-form__submit button-compact"
                              onClick={() => void download(q)}
                              disabled={isDownloading}
                            >
                              {isDownloading ? 'Téléchargement...' : 'Télécharger'}
                            </button>
                            <button
                              type="button"
                              className="catalog-admin-actions__delete"
                              onClick={() => {
                                requestDelete(q.id);
                              }}
                            >
                              Supprimer
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </AdminTableShell>
          )}
        </StableContent>
        <ConfirmDialog
          open={confirmOpen}
          title="Supprimer ce devis ?"
          description={<div>Cette action est définitive.</div>}
          confirmLabel="Oui, supprimer"
          cancelLabel="Annuler"
          onCancel={cancelDelete}
          onConfirm={() => void confirmDelete()}
        />
      </PageContainer>
    </SiteLayout>
  );
};
