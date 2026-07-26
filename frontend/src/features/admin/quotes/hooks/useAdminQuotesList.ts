import { useEffect, useMemo, useState } from 'react';
import {
  fetchAdminQuotes,
  deleteAdminQuote,
  duplicateAdminQuote,
  sendAdminQuoteEmail,
} from '@/features/quotes/api/quotesApi';
import type { QuoteDto } from '@/features/quotes/types/quoteTypes';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useToast } from '@/shared/components/ui/toast';
import { useConfirm } from '@/shared/components/ui/confirm';
import { usePrompt } from '@/shared/components/ui/prompt';
import { fetchAdminQuoteMetadata, type QuoteMetadataOption } from '@/features/quotes/api/adminQuotesApi';
export const useAdminQuotesList = () => {
  const toast = useToast();
  const confirm = useConfirm();
  const prompt = usePrompt();
  const [quotes, setQuotes] = useState<QuoteDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const [statusOptions, setStatusOptions] = useState<QuoteMetadataOption[]>([]);
  useEffect(() => {
    void fetchAdminQuoteMetadata().then((metadata) => setStatusOptions(metadata.statuses)).catch(() => undefined);
  }, []);
  useEffect(() => {
    setLoading(true);
    void fetchAdminQuotes({
      q: search.trim() || undefined,
      status: filterStatus,
    })
      .then(setQuotes)
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger les devis.')))
      .finally(() => setLoading(false));
  }, [search, filterStatus]);
  const filtered = useMemo(() => {
    const from = fromDate ? new Date(fromDate).getTime() : null;
    const to = toDate ? new Date(toDate).getTime() : null;
    return quotes.filter((quote) => {
      const created = quote.createdAt ? new Date(quote.createdAt).getTime() : null;
      return (
        (from === null || (created !== null && created >= from)) &&
        (to === null || (created !== null && created <= to))
      );
    });
  }, [quotes, fromDate, toDate]);
  const handleDelete = async (id: number) => {
    const quote = quotes.find((item) => item.id === id);
    if (
      !(await confirm({
        title: 'Supprimer le devis',
        description: `Supprimer ${quote ? `le devis ${quote.number}` : 'ce devis'} ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    try {
      await deleteAdminQuote(id);
      setQuotes((items) => items.filter((item) => item.id !== id));
      setMessage('Devis supprimé.');
      toast.show('Devis supprimé.', { variant: 'success' });
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Suppression impossible.');
      setError(msg);
      toast.show(msg, { variant: 'error' });
    }
  };
  const handleDuplicate = async (id: number) => {
    try {
      setQuotes((items) => items);
      const copy = await duplicateAdminQuote(id);
      setQuotes((items) => [copy, ...items]);
      setMessage('Devis dupliqué.');
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Duplication impossible.');
      setError(msg);
      toast.show(msg, { variant: 'error' });
    }
  };
  const handleSendEmail = async (id: number) => {
    const quote = quotes.find((item) => item.id === id);
    const to = await prompt({
      title: 'Envoyer le devis',
      description: quote?.number
        ? `Choisissez le destinataire du devis ${quote.number}.`
        : undefined,
      label: 'Destinataire (e-mail)',
      defaultValue: quote?.customer?.email ?? '',
      inputType: 'email',
      inputMode: 'email',
      confirmLabel: 'Envoyer',
      cancelLabel: 'Annuler',
    });
    if (to === null) return;
    try {
      const response = await sendAdminQuoteEmail(id, to);
      setQuotes((items) =>
        items.map((item) =>
          item.id === id
            ? {
                ...item,
                statusCode: response.statusCode ?? 'sent',
                statusLabel: response.statusLabel ?? item.statusLabel,
                status: response.statusCode ?? 'sent',
                sentAt: new Date().toISOString(),
              }
            : item,
        ),
      );
      const msg = getHttpErrorMessage(response, 'E-mail envoyé.');
      setMessage(msg);
      toast.show(msg, { variant: 'success' });
    } catch (e) {
      const msg = getHttpErrorMessage(e, 'Envoi impossible.');
      setError(msg);
      toast.show(msg, { variant: 'error' });
    }
  };
  return {
    loading,
    error,
    message,
    search,
    setSearch,
    filterStatus,
    setFilterStatus,
    statusOptions,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    filtered,
    handleDelete,
    handleDuplicate,
    handleSendEmail,
  };
};
