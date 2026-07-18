import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { fetchAdminQuotes, deleteAdminQuote, duplicateAdminQuote, formatQuoteStatus, sendAdminQuoteEmail } from '@/features/quotes/api';
import { useToast } from '@/shared/components/ui/toast';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(cents / 100);

const formatDate = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleDateString('fr-FR');
};

export const QuotesListPage = () => {
  const toast = useToast();
  useDocumentTitle('Admin - Devis');
  const [quotes, setQuotes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchAdminQuotes({ q: search.trim() || undefined, status: filterStatus })
      .then((items) => setQuotes(items))
      .catch((err: any) => setError(err?.message ?? 'Impossible de charger les devis.'))
      .finally(() => setLoading(false));
  }, [search, filterStatus]);

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
    const quote = quotes.find((item) => item.id === id);
    const quoteLabel = quote ? `le devis ${quote.number}` : 'ce devis';

    if (!window.confirm(`Supprimer ${quoteLabel} ?`)) return;
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

  const handleSendEmail = async (id: number) => {
    const quote = quotes.find((item) => item.id === id);
    const defaultEmail = quote?.customer?.email ?? '';
    const to = window.prompt('Destinataire (e-mail)', defaultEmail) ?? undefined;
    if (to === undefined) return;

    setError(null);
    setMessage(null);
    try {
      const response = await sendAdminQuoteEmail(id, to);
      const nextMessage = response?.message ?? 'E-mail envoyé.';
      setMessage(nextMessage);
      try {
        toast.show(nextMessage, { variant: 'success' });
      } catch {}
    } catch (e: any) {
      const msg = e?.message ?? 'Envoi impossible.';
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    }
  };

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
        <div className="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
          <table className="catalog-admin-table">
            <thead>
              <tr>
                <th scope="col">Numéro</th>
                <th scope="col">Client</th>
                <th scope="col">E-mail</th>
                <th scope="col">Statut</th>
                <th scope="col">Fin de validité</th>
                <th scope="col">Total TTC</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((q) => (
                <tr key={q.id}>
                  <th scope="row">
                    <Link
                      to={`/admin/quotes/${q.id}`}
                      className="catalog-admin-table__primary-link"
                    >
                      <strong>{q.number}</strong>
                    </Link>
                  </th>
                  <td>
                    <strong>{q.customer?.name ?? '-'}</strong>
                  </td>
                  <td>{q.customer?.email ?? '-'}</td>
                  <td>{formatQuoteStatus(q.status)}</td>
                  <td>{formatDate(q.validUntil)}</td>
                  <td>{formatPrice(q?.totals?.ttc ?? 0)}</td>
                  <td>
                    <div className="catalog-admin-actions">
                      <Link
                        to={`/admin/quotes/${q.id}/edit`}
                        className="catalog-admin-actions__edit"
                        aria-label="Modifier"
                      >
                        Modifier
                      </Link>
                      <button
                        type="button"
                        className="catalog-admin-actions__edit"
                        onClick={() => void handleSendEmail(q.id)}
                        aria-label="Envoyer"
                      >
                        Envoyer
                      </button>
                      <button
                        type="button"
                        className="catalog-admin-actions__edit"
                        onClick={() => void handleDuplicate(q.id)}
                        aria-label="Dupliquer"
                      >
                        Dupliquer
                      </button>
                      <button
                        type="button"
                        className="catalog-admin-actions__delete"
                        onClick={() => void handleDelete(q.id)}
                        aria-label="Supprimer"
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
