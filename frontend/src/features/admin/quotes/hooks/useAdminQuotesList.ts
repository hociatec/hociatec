import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  fetchAdminQuotes,
  deleteAdminQuote,
  duplicateAdminQuote,
  sendAdminQuoteEmail,
} from '@/features/quotes/publicApi';
import type { QuoteDto } from '@/features/quotes/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useToast } from '@/shared/components/ui/toast';
import { useConfirm } from '@/shared/components/ui/confirm';
import { usePrompt } from '@/shared/components/ui/prompt';
import { fetchAdminQuoteMetadata, type QuoteMetadataOption } from '@/features/quotes/publicApi';
import { adminQuoteQueryKeys } from '@/shared/lib/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

export const useAdminQuotesList = () => {
  const toast = useToast();
  const confirm = useConfirm();
  const prompt = usePrompt();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [fromDate, setFromDate] = useState<string | null>(null);
  const [toDate, setToDate] = useState<string | null>(null);
  const metadataQuery = useQuery({
    queryKey: adminQuoteQueryKeys.metadata(),
    queryFn: fetchAdminQuoteMetadata,
  });
  const quotesQuery = useQuery<QuoteDto[], Error>({
    queryKey: adminQuoteQueryKeys.list(search, filterStatus),
    queryFn: () =>
      fetchAdminQuotes(omitUndefinedProperties({
        q: search.trim() || undefined,
        status: filterStatus,
      })),
  });
  const quotes = quotesQuery.data ?? [];
  const deleteMutation = useMutation({
    mutationFn: deleteAdminQuote,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: ['admin', 'quotes'] });
      setMessage(response.message ?? 'Le devis a bien été supprimé.');
      toast.show(response.message ?? 'Le devis a bien été supprimé.', { variant: 'success' });
    },
    onError: (e) => {
      const msg = getHttpErrorMessage(e, 'Suppression impossible.');
      setError(msg);
      toast.show(msg, { variant: 'error' });
    },
  });
  const duplicateMutation = useMutation({
    mutationFn: duplicateAdminQuote,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: ['admin', 'quotes'] });
      setMessage(response.message ?? 'Le devis a bien été dupliqué.');
    },
    onError: (e) => {
      const msg = getHttpErrorMessage(e, 'Duplication impossible.');
      setError(msg);
      toast.show(msg, { variant: 'error' });
    },
  });
  const sendEmailMutation = useMutation({
    mutationFn: ({ id, to }: { id: number; to: string }) => sendAdminQuoteEmail(id, to),
    onSuccess: (response, { id }) => {
      queryClient.setQueryData<QuoteDto[]>(
        adminQuoteQueryKeys.list(search, filterStatus),
        (items = []) =>
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
    },
    onError: (e) => {
      const msg = getHttpErrorMessage(e, 'Envoi impossible.');
      setError(msg);
      toast.show(msg, { variant: 'error' });
    },
  });
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
    deleteMutation.mutate(id);
  };
  const handleDuplicate = async (id: number) => {
    duplicateMutation.mutate(id);
  };
  const handleSendEmail = async (id: number) => {
    const quote = quotes.find((item) => item.id === id);
    const to = await prompt({
      title: 'Envoyer le devis',
      ...(quote?.number
        ? { description: `Choisissez le destinataire du devis ${quote.number}.` }
        : {}),
      label: 'Destinataire (e-mail)',
      defaultValue: quote?.customer?.email ?? '',
      inputType: 'email',
      inputMode: 'email',
      confirmLabel: 'Envoyer',
      cancelLabel: 'Annuler',
    });
    if (to === null) return;
    sendEmailMutation.mutate({ id, to });
  };
  return {
    loading: quotesQuery.isLoading,
    error:
      error ??
      (quotesQuery.error
        ? getHttpErrorMessage(quotesQuery.error, 'Impossible de charger les devis.')
        : null),
    message,
    search,
    setSearch,
    filterStatus,
    setFilterStatus,
    statusOptions: metadataQuery.data?.statuses ?? ([] as QuoteMetadataOption[]),
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
