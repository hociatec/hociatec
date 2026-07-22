import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { deleteAdminQuoteService, fetchAdminQuoteServices, type QuoteServiceDto } from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatEuroCents } from '@/shared/lib/formatters';

const formatDuration = (service: QuoteServiceDto) => {
  if (!service?.durationValue || !service?.durationUnit) return '—';

  if (service.durationUnit === 'day') {
    return `${service.durationValue} jour${service.durationValue > 1 ? 's' : ''}`;
  }

  return `${service.durationValue} heure${service.durationValue > 1 ? 's' : ''}`;
};

export const ServicesListPage = () => {
  useDocumentTitle('Admin - Services');
  const [services, setServices] = useState<QuoteServiceDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const confirm = useConfirm();
  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchAdminQuoteServices()
      .then((items) => setServices(items))
      .catch((e) => setError(getHttpErrorMessage(e, 'Chargement impossible.')))
      .finally(() => setLoading(false));
  }, []);

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return services.filter((s) => term.length === 0 || s.title.toLowerCase().includes(term));
  }, [services, search]);

  const handleDelete = async (id: number) => {
    const service = services.find((item) => item.id === id);
    const serviceLabel = service ? `"${service.title}"` : 'ce service';

    const confirmed = await confirm({
      title: 'Supprimer le service',
      description: `Supprimer ${serviceLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) return;
    await deleteAdminQuoteService(id);
    setServices((prev) => prev.filter((s) => s.id !== id));
    setMessage('Service supprime.');
  };

  return (
    <PageContainer size="admin"
      title="Services"
      headerActions={
        <Link to="/admin/services/new" className="register-form__submit">
          Nouveau service
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          Gérez ici votre catalogue de services, leurs tarifs, leur mode de facturation et leur durée estimée.
        </p>
      </div>
      <FilterBar>
        <SearchFilter value={search} onChange={setSearch} placeholder="Rechercher..." />
      </FilterBar>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filtered.length === 0}
        loadingLabel="Chargement..."
        emptyLabel="Aucun service."
      >
        <AdminTableShell>
        <table className="catalog-admin-table">
          <thead>
            <tr>
              <th scope="col">Titre</th>
              <th scope="col">Mode de facturation</th>
              <th scope="col">Durée</th>
              <th scope="col">Prix</th>
              <th scope="col">TVA</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map((s) => (
              <tr key={s.id}>
                <th scope="row">
                  <div className="catalog-admin-product-cell">
                    <strong>{s.title}</strong>
                    <span className="muted">{s.description ?? ''}</span>
                  </div>
                </th>
                <td>{s.unit?.trim() || 'Prix fixe'}</td>
                <td>{formatDuration(s)}</td>
                <td>{formatEuroCents(s.priceCents)}</td>
                <td>{s.vatRate?.toFixed(2) ?? '0'}%</td>
                <td>
                  <div className="catalog-admin-actions">
                    <Link
                      to={`/admin/services/${s.id}/edit`}
                      className="catalog-admin-actions__edit"
                      aria-label={`Modifier le service ${s.title}`}
                    >
                      Modifier
                    </Link>
                    <button
                      type="button"
                      className="catalog-admin-actions__delete"
                      onClick={() => void handleDelete(s.id)}
                      aria-label={`Supprimer le service ${s.title}`}
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
