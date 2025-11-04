import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { deletePrestation, fetchAdminPrestations } from '@/features/admin/appointments/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import type { Prestation } from '@/features/appointments/types';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const PrestationsListPage = () => {
  useDocumentTitle('Admin - Prestations');

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [prestations, setPrestations] = useState<Prestation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) {
      return;
    }

    void loadPrestations();
  }, [isAdmin]);

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

  const handleDelete = async (prestationId: number) => {
    if (!window.confirm('Supprimer cette prestation ?')) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      await deletePrestation(prestationId);
      await loadPrestations();
      setMessage('Prestation supprimee.');
    } catch (err: any) {
      setError(err?.message ?? 'Impossible de supprimer la prestation');
    }
  };

  const formatPrice = (priceCents: number) => (priceCents / 100).toFixed(2);

  if (guardLoading) {
    return (
      <PageContainer title="Prestations">
        <p className="muted">Verification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title="Prestations">
        <div className="register-form__alert">Acces restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title="Prestations"
      headerActions={
        <Link to="/admin/appointments/prestations/new" className="register-form__submit">
          Ajouter une prestation
        </Link>
      }
    >
      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <p className="muted">Chargement des prestations...</p>
      ) : prestations.length === 0 ? (
        <p className="muted">Aucune prestation enregistree.</p>
      ) : (
        <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: 12 }}>
          <thead>
            <tr>
              <th style={{ textAlign: 'left', padding: 8 }}>Nom</th>
              <th style={{ textAlign: 'left', padding: 8 }}>Duree (min)</th>
              <th style={{ textAlign: 'left', padding: 8 }}>Prix (EUR)</th>
              <th style={{ textAlign: 'center', padding: 8, width: 200 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {prestations.map((prestation) => (
              <tr key={prestation.id} style={{ borderTop: '1px solid rgba(148,163,184,.25)' }}>
                <td style={{ padding: 8 }}>{prestation.name}</td>
                <td style={{ padding: 8 }}>{prestation.durationMinutes}</td>
                <td style={{ padding: 8 }}>{formatPrice(prestation.priceCents)}</td>
                <td style={{ padding: 8, display: 'flex', gap: 8, justifyContent: 'center' }}>
                  <Link
                    to={`/admin/appointments/prestations/${prestation.id}/edit`}
                    className="register-form__submit"
                    style={{ background: '#e5e7eb', color: '#111827' }}
                  >
                    Modifier
                  </Link>
                  <button
                    type="button"
                    className="register-form__submit"
                    style={{ background: '#fee2e2', color: '#991b1b' }}
                    onClick={() => void handleDelete(prestation.id)}
                  >
                    Supprimer
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </PageContainer>
  );
};

