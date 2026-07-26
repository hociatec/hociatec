import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { deletePrestation, fetchAdminPrestations } from '@/features/admin/appointments/api';
import type { Prestation } from '@/features/appointments/types/appointments';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatEuroCents } from '@/shared/lib/formatters';

export const PrestationsListPage = () => {
  useDocumentTitle('Admin - Prestations de rendez-vous');

  const [prestations, setPrestations] = useState<Prestation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const confirm = useConfirm();

  const loadPrestations = async () => {
    setLoading(true);
    setError(null);

    try {
      const items = await fetchAdminPrestations();
      setPrestations(items);
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Erreur lors du chargement des prestations'));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadPrestations();
  }, []);

  const handleDelete = async (prestationId: number) => {
    const prestation = prestations.find((item) => item.id === prestationId);
    const prestationLabel = prestation ? `"${prestation.name}"` : 'cette prestation';

    const confirmed = await confirm({
      title: 'Supprimer la prestation',
      description: `Supprimer ${prestationLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deletePrestation(prestationId);
      await loadPrestations();
      setMessage('Prestation supprimée.');
    } catch (err) {
      setError(getHttpErrorMessage(err, 'Impossible de supprimer la prestation'));
    }
  };

  return (
    <PageContainer
      size="admin"
      title="Prestations de rendez-vous"
      headerActions={
        <PrimaryLink to="/admin/appointments/prestations/new">Ajouter une prestation</PrimaryLink>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {prestations.length} prestation{prestations.length > 1 ? 's' : ''} au catalogue.
        </p>
        <p className="text-sm text-stone-500">
          Ces prestations sont utilisées uniquement pour la prise de rendez-vous et la planification
          des interventions.
        </p>
        <p className="text-sm text-brand-700">
          Pour le catalogue de services global, utilisez{' '}
          <Link to="/admin/services" className="font-semibold underline">
            Services
          </Link>
          .
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={prestations.length === 0}
        loadingLabel="Chargement des prestations..."
        emptyLabel="Aucune prestation enregistrée."
      >
        <AdminTableShell>
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Nom</th>
                <th scope="col">Durée</th>
                <th scope="col">Prix</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {prestations.map((prestation) => (
                <tr key={prestation.id}>
                  <th scope="row">{prestation.name}</th>
                  <td>{prestation.durationMinutes} min</td>
                  <td>{formatEuroCents(prestation.priceCents)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/appointments/prestations/${prestation.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label={`Modifier la prestation ${prestation.name}`}
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(prestation.id)}
                        aria-label={`Supprimer la prestation ${prestation.name}`}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
