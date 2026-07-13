import { useEffect, useState } from 'react';
import { deleteMyQuote, fetchMyQuotes } from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format((cents ?? 0) / 100);

export const MyQuotesPage = () => {
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const toast = useToast();

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchMyQuotes()
      .then((rows) => setItems(rows))
      .catch((e: any) => setError(e?.message ?? 'Impossible de charger vos devis.'))
      .finally(() => setLoading(false));
  }, []);

  const handleDelete = async () => {
    if (!deletingId) return;
    try {
      await deleteMyQuote(deletingId);
      setItems((list) => list.filter((q) => q.id !== deletingId));
      toast.show('Devis supprimÃ©.', { variant: 'success' });
    } catch (e: any) {
      toast.show(e?.message ?? 'Impossible de supprimer le devis.', { variant: 'error' });
    } finally {
      setDeletingId(null);
      setConfirmOpen(false);
    }
  };

  return (
    <SiteLayout>
      <PageContainer title="Mes devis">
        {loading ? (
          <p className="muted">Chargement...</p>
        ) : error ? (
          <div className="register-form__alert">{error}</div>
        ) : items.length === 0 ? (
          <p className="muted">Aucun devis.</p>
        ) : (
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>NumÃ©ro</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Total TTC</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {items.map((q) => (
                <tr key={q.id}>
                  <td>{q.number}</td>
                  <td>{q.status}</td>
                  <td>{new Date(q.createdAt).toLocaleDateString('fr-FR')}</td>
                  <td>{formatPrice(q?.totals?.ttc ?? 0)}</td>
                  <td>
                    <button
                      type="button"
                      className="catalog-admin-actions__delete"
                      onClick={() => { setDeletingId(q.id); setConfirmOpen(true); }}
                    >
                      Supprimer
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        <ConfirmDialog
          open={confirmOpen}
          title="Supprimer ce devis ?"
          description={<div>Cette action est dÃ©finitive.</div>}
          confirmLabel="Oui, supprimer"
          cancelLabel="Annuler"
          onCancel={() => { setConfirmOpen(false); setDeletingId(null); }}
          onConfirm={() => void handleDelete()}
        />

      </PageContainer>
    </SiteLayout>
  );
};
