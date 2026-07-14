import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { deletePrestation, fetchAdminPrestations } from '@/features/admin/appointments/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import type { Prestation } from '@/features/appointments/types';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

const formatPrice = (priceCents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(priceCents / 100);

export const PrestationsListPage = () => {
  useDocumentTitle('Admin - Prestations de rendez-vous');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [prestations, setPrestations] = useState<Prestation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const loadPrestations = async () => {
    setLoading(true);
    setError(null);

    try {
      const items = await fetchAdminPrestations();
      setPrestations(items);
    } catch (err: any) {
      setError(err?.message ?? 'Erreur lors du chargement des prestations');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!isAdmin) return;
    void loadPrestations();
  }, [isAdmin]);

  const handleDelete = async (prestationId: number) => {
    if (!window.confirm('Supprimer cette prestation ?')) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deletePrestation(prestationId);
      await loadPrestations();
      setMessage('Prestation supprimée.');
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de supprimer la prestation');
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title="Prestations de rendez-vous">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title="Prestations de rendez-vous">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title="Prestations de rendez-vous"
      headerActions={
        <Link
          to="/admin/appointments/prestations/new"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Ajouter une prestation
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {prestations.length} prestation{prestations.length > 1 ? 's' : ''} au catalogue.
        </p>
        <p className="text-sm text-slate-500">
          Ces prestations sont utilisées uniquement pour la prise de rendez-vous et la planification des interventions.
        </p>
        <p className="text-sm text-sky-700">
          Pour le catalogue de services global, utilisez{' '}
          <Link to="/admin/services" className="font-semibold underline">
            Services
          </Link>
          .
        </p>
      </div>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des prestations...
        </div>
      ) : prestations.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucune prestation enregistrée.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Durée</th>
                <th>Prix</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {prestations.map((prestation) => (
                <tr key={prestation.id}>
                  <td>{prestation.name}</td>
                  <td>{prestation.durationMinutes} min</td>
                  <td>{formatPrice(prestation.priceCents)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/appointments/prestations/${prestation.id}/edit`}
                        className="catalog-admin-actions__edit"
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(prestation.id)}
                      >
                        Supprimer
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </PageContainer>
  );
};
