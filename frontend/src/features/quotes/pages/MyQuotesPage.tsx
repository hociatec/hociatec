import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { deleteMyQuote, fetchMyQuotes, formatQuoteStatus, generateMyQuotePdf, type QuoteDto } from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { EmptyState, FeedbackMessage, StableContent } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const MyQuotesPage = () => {
  useDocumentTitle('Mes devis');

  const [items, setItems] = useState<QuoteDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const toast = useToast();

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchMyQuotes()
      .then((rows) => setItems(rows))
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger vos devis.')))
      .finally(() => setLoading(false));
  }, []);

  const handleDelete = async () => {
    if (!deletingId) return;
    try {
      await deleteMyQuote(deletingId);
      setItems((list) => list.filter((q) => q.id !== deletingId));
      toast.show('Devis supprimé.', { variant: 'success' });
    } catch (e) {
      toast.show(getHttpErrorMessage(e, 'Impossible de supprimer le devis.'), { variant: 'error' });
    } finally {
      setDeletingId(null);
      setConfirmOpen(false);
    }
  };

  const handleDownload = async (quote: QuoteDto) => {
    setDownloadingId(quote.id);
    try {
      const blob = await generateMyQuotePdf(quote.id);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${quote.number}.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (e) {
      toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), { variant: 'error' });
    } finally {
      setDownloadingId(null);
    }
  };

  return (
    <SiteLayout>
      <PageContainer size="medium" title="Mes devis">
        <StableContent loading={loading} hasContent={items.length > 0 || !loading} loadingLabel="Chargement...">
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
                    <td>{q.statusLabel ?? formatQuoteStatus(q.statusCode ?? q.status)}</td>
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
                          onClick={() => void handleDownload(q)}
                          disabled={isDownloading}
                        >
                          {isDownloading ? 'Téléchargement...' : 'Télécharger'}
                        </button>
                        <button
                          type="button"
                          className="catalog-admin-actions__delete"
                          onClick={() => {
                            setDeletingId(q.id);
                            setConfirmOpen(true);
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
          onCancel={() => {
            setConfirmOpen(false);
            setDeletingId(null);
          }}
          onConfirm={() => void handleDelete()}
        />
      </PageContainer>
    </SiteLayout>
  );
};
