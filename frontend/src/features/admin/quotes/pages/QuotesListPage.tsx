import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

import { fetchAdminQuotes, deleteAdminQuote, duplicateAdminQuote, formatQuoteStatus, sendAdminQuoteEmail, type QuoteDto } from '@/features/quotes/api/quotesApi';
import { useToast } from '@/shared/components/ui/toast';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { useConfirm } from '@/shared/components/ui/confirm';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { usePrompt } from '@/shared/components/ui/prompt';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { DateRangeFilter } from '@/shared/components/filters/DateRangeFilter';
import { formatDateInputForDisplay, formatEuroCents } from '@/shared/lib/formatters';

export const QuotesListPage = () => {
  const toast = useToast();
  const confirm = useConfirm();
  const prompt = usePrompt();
  useDocumentTitle('Admin - Devis');
  const [quotes, setQuotes] = useState<QuoteDto[]>([]);
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
      .catch((err) => setError(getHttpErrorMessage(err, 'Impossible de charger les devis.')))
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

    const confirmed = await confirm({
      title: 'Supprimer le devis',
      description: `Supprimer ${quoteLabel} ?`,
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    });

    if (!confirmed) return;
    setError(null);
    setMessage(null);
    try {
      await deleteAdminQuote(id);
      setQuotes((prev) => prev.filter((q) => q.id !== id));
      setMessage('Devis supprimé.');
      try {
        toast.show('Devis supprimé.', { variant: 'success' });
      } catch {}
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Suppression impossible.');
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
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Duplication impossible.');
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    }
  };

  const handleSendEmail = async (id: number) => {
    const quote = quotes.find((item) => item.id === id);
    const defaultEmail = quote?.customer?.email ?? '';
    const to = await prompt({
      title: 'Envoyer le devis',
      description: quote?.number ? `Choisissez le destinataire du devis ${quote.number}.` : undefined,
      label: 'Destinataire (e-mail)',
      defaultValue: defaultEmail,
      inputType: 'email',
      inputMode: 'email',
      confirmLabel: 'Envoyer',
      cancelLabel: 'Annuler',
    });
    if (to === null) return;

    setError(null);
    setMessage(null);
    try {
      const response = await sendAdminQuoteEmail(id, to);
      const nextMessage = getHttpErrorMessage(response, 'E-mail envoyé.');
      setQuotes((prev) =>
        prev.map((item) =>
          item.id === id
            ? { ...item, statusCode: 'sent', statusLabel: 'Envoyé', status: 'Envoyé', sentAt: new Date().toISOString() }
            : item,
        ),
      );
      setMessage(nextMessage);
      try {
        toast.show(nextMessage, { variant: 'success' });
      } catch {}
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Envoi impossible.');
      setError(msg);
      try {
        toast.show(msg, { variant: 'error' });
      } catch {}
    }
  };

  return (
    <PageContainer size="admin"
      title="Devis"
      headerActions={
        <PrimaryLink to="/admin/quotes/new">
          Nouveau devis
        </PrimaryLink>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {filtered.length} devis affiché{filtered.length > 1 ? 's' : ''}.
        </p>
        <p className="text-sm text-stone-500">
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

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={filtered.length === 0}
        loadingLabel="Chargement des devis..."
        emptyLabel="Aucun devis."
      >
        <AdminTableShell>
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
                  <td>{q.statusLabel ?? formatQuoteStatus(q.statusCode ?? q.status)}</td>
                  <td>{formatDateInputForDisplay(q.validUntil)}</td>
                  <td>{formatEuroCents(q?.totals?.ttc ?? 0)}</td>
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
        </AdminTableShell>
      </AdminListState>
    </PageContainer>
  );
};
