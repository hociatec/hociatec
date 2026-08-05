import { useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  convertAdminQuoteToOrder,
  fetchAdminQuote,
  generateAdminQuotePdf,
  sendAdminQuoteEmail,
  updateAdminQuoteStatus,
} from '@/features/quotes/api/quotesApi';
import type { QuoteDto, QuoteStatus } from '@/features/quotes/types/quoteTypes';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { usePrompt } from '@/shared/components/ui/prompt';
import { useToast } from '@/shared/components/ui/toast';
import { adminQuoteQueryKeys } from '@/shared/lib/queryKeys';

export const useAdminQuoteDetail = () => {
  const { quoteId } = useParams();
  const id = Number(quoteId);
  const navigate = useNavigate();
  const prompt = usePrompt();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [actionKey, setActionKey] = useState<string | null>(null);
  const quoteQuery = useQuery<QuoteDto, Error>({
    queryKey: adminQuoteQueryKeys.detail(Number.isFinite(id) ? id : null),
    queryFn: () => fetchAdminQuote(id),
    enabled: Number.isFinite(id) && id > 0,
  });
  const quote = quoteQuery.data ?? null;
  const refresh = async () => {
    if (!quote) return;
    await queryClient.invalidateQueries({ queryKey: adminQuoteQueryKeys.detail(quote.id) });
  };
  const downloadMutation = useMutation({
    mutationFn: (currentQuote: QuoteDto) => generateAdminQuotePdf(currentQuote.id),
    onSuccess: (blob, currentQuote) => downloadBlob(blob, `${currentQuote.number}.pdf`),
    onError: async (e) =>
      toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), {
        variant: 'error',
      }),
  });
  const sendEmailMutation = useMutation({
    mutationFn: ({ currentQuote, to }: { currentQuote: QuoteDto; to: string }) =>
      sendAdminQuoteEmail(currentQuote.id, to),
    onMutate: () => setActionKey('send'),
    onSuccess: async (response) => {
      await refresh();
      toast.show(getHttpErrorMessage(response, 'Devis envoyé.'), { variant: 'success' });
    },
    onError: (e) => toast.show(getHttpErrorMessage(e, 'Envoi impossible.'), { variant: 'error' }),
    onSettled: () => setActionKey(null),
  });
  const statusMutation = useMutation({
    mutationFn: ({ currentQuote, status }: { currentQuote: QuoteDto; status: QuoteStatus }) =>
      updateAdminQuoteStatus(currentQuote.id, status),
    onMutate: ({ status }) => setActionKey(status),
    onSuccess: (response) => {
      queryClient.setQueryData(adminQuoteQueryKeys.detail(response.data.id), response.data);
      toast.show(response.message ?? 'Le statut du devis a bien été mis à jour.', {
        variant: 'success',
      });
    },
    onError: (e) => toast.show(getHttpErrorMessage(e, 'Mise à jour impossible.'), { variant: 'error' }),
    onSettled: () => setActionKey(null),
  });
  const convertMutation = useMutation({
    mutationFn: (currentQuote: QuoteDto) => convertAdminQuoteToOrder(currentQuote.id),
    onMutate: () => setActionKey('convert'),
    onSuccess: async (response) => {
      const order = response?.order;
      toast.show(order ? `Commande ${order.number} créée.` : 'Commande créée.', {
        variant: 'success',
      });
      if (order?.id) navigate(`/admin/orders/${order.id}`);
      else await refresh();
    },
    onError: (e) => toast.show(getHttpErrorMessage(e, 'Conversion impossible.'), { variant: 'error' }),
    onSettled: () => setActionKey(null),
  });
  const handleDownload = async () => {
    if (!quote) return;
    downloadMutation.mutate(quote);
  };
  const handleSendEmail = async () => {
    if (!quote) return;
    const to = await prompt({
      title: 'Envoyer le devis',
      description: `Choisissez le destinataire du devis ${quote.number}.`,
      label: 'Destinataire (e-mail)',
      defaultValue: quote.customer?.email ?? '',
      inputType: 'email',
      inputMode: 'email',
      confirmLabel: 'Envoyer',
      cancelLabel: 'Annuler',
    });
    if (to === null) return;
    sendEmailMutation.mutate({ currentQuote: quote, to });
  };
  const handleUpdateStatus = async (status: QuoteStatus) => {
    if (!quote) return;
    statusMutation.mutate({ currentQuote: quote, status });
  };
  const handleConvertToOrder = async () => {
    if (!quote) return;
    convertMutation.mutate(quote);
  };
  return {
    quote,
    loading: quoteQuery.isLoading,
    error:
      !Number.isFinite(id) || id <= 0
        ? 'Devis introuvable.'
        : quoteQuery.error
          ? getHttpErrorMessage(quoteQuery.error, 'Impossible de charger le devis.')
          : null,
    downloading: downloadMutation.isPending,
    actionLoading: actionKey,
    handleDownload,
    handleSendEmail,
    handleUpdateStatus,
    handleConvertToOrder,
    navigate,
  };
};
