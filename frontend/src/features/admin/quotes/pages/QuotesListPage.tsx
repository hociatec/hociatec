import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { fetchAdminQuotes, deleteAdminQuote, duplicateAdminQuote } from '@/features/quotes/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import { useToast } from '@/shared/components/ui/toast';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(cents / 100);

export const QuotesListPage = () => {
  const toast = useToast();
  useDocumentTitle('Admin - Devis');
  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [quotes, setQuotes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);

  useEffect(() => {
    if (!isAdmin) return;
    setLoading(true);
    setError(null);
    void fetchAdminQuotes({ q: search.trim() || undefined, status: filterStatus })
      .then((items) => setQuotes(items))
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les devis.'))
      .finally(() => setLoading(false));
  }, [isAdmin, search, filterStatus]);

  const filtered = useMemo(() => {
    const fromTs = fromDate ? new Date(fromDate).getTime() : null;
    const toTs = toDate ? new Date(toDate).getTime() : null;
    return quotes.filter((q) => {
      const created = q?.createdAt ? new Date(q.createdAt).getTime() : null;
      const matchFrom = fromTs === null || (created !== null && created >= fromTs);
      const matchTo = toTs === null || (created !== null && created <= toTs);
      return matchFrom && matchTo;
    });
  }, [quotes, fromDate, toDate]);

  const handleDelete = async (id: number) => {
    if (!window.confirm('Supprimer ce devis ?')) return;
    setError(null);
    setMessage(null);
    try {
      await deleteAdminQuote(id);
      setQuotes((prev) => prev.filter((q) => q.id !== id));
      setMessage('Devis supprimé.');
      try {
        toast.show('Devis supprimé.', { variant: 'success' });
      } catch {}
    } catch (e: any) {
      const msg = e?.message ?? 'Suppression impossible.';
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    }
  };

  const handleDuplicate = async (id: number) => {
    setError(null);
    try {
      const copy = await duplicateAdminQuote(id);
      setQuotes((prev) => [copy, ...prev]);
      setMessage('Devis dupliqué.');
    } catch (e: any) {
      const msg = e?.message ?? 'Duplication impossible.';
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title="Devis">
        <p className="muted">Vérification des droits...</p>
      </PageContainer>
    );
  }
  if (!isAdmin) {
    return (
      <PageContainer title="Devis">
        <div className="register-form__alert">Accès restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title="Devis"
      headerActions={
        <Link
          to="/devis/nouveau"
          className="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Nouveau devis
        </Link>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-slate-600">
          {filtered.length} devis affiché{filtered.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-slate-500">
          Filtrez par numéro, client, statut et période.
        </p>
      </div>

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par numéro, client..."
        />
        <SelectFilter
          value={filterStatus}
          onChange={setFilterStatus}
          options={[
            { value: 'all', label: 'Tous les statuts' },
            { value: 'draft', label: 'Brouillon' },
            { value: 'sent', label: 'Envoyé' },
            { value: 'accepted', label: 'Accepté' },
            { value: 'refused', label: 'Refusé' },
            { value: 'expired', label: 'Expiré' },
          ]}
          ariaLabel="Statut"
        />
        <DateRangeFilter
          from={fromDate}
          to={toDate}
          onChange={({ from, to }) => {
            setFromDate(from);
            setToDate(to);
          }}
        />
      </FilterBar>

      {error && <div className="register-form__alert">{error}</div>}
      {message && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Chargement des devis...
        </div>
      ) : filtered.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-slate-600">
          Aucun devis.
        </div>
      ) : (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th>Numéro</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Total TTC</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((q) => (
                <tr key={q.id}>
                  <td>
                    <strong>{q.number}</strong>
                    <div className="muted">{new Date(q.createdAt).toLocaleDateString('fr-FR')}</div>
                  </td>
                  <td>
                    <div>
                      <strong>{q.customer?.name ?? '-'}</strong>
                      <div className="muted">{q.customer?.email ?? ''}</div>
                    </div>
                  </td>
                  <td>{q.status}</td>
                  <td>{formatPrice(q?.totals?.ttc ?? 0)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link to={`/admin/quotes/${q.id}/edit`} className="catalog-admin-actions__edit">
                        Ouvrir
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__edit"
                        onClick={() => void handleDuplicate(q.id)}
                      >
                        Dupliquer
                      </button>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(q.id)}
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
