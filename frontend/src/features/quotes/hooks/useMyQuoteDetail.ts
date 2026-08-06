import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useParams } from 'react-router';
import { acceptMyQuote, fetchMyQuote, generateMyQuotePdf, refuseMyQuote } from '../api/quotesApi';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { useToast } from '@/shared/components/ui/toast';
import { quoteQueryKeys } from '@/features/quotes/queryKeys';

export const useMyQuoteDetail = () => {
  const { quoteId } = useParams();
  const id = Number(quoteId);
  const [downloading, setDownloading] = useState(false);
  const [updatingStatus, setUpdatingStatus] = useState<'accept' | 'refuse' | null>(null);
  const toast = useToast();
  const queryClient = useQueryClient();
  const quoteQuery = useQuery({
    queryKey: quoteQueryKeys.mineDetail(Number.isFinite(id) && id > 0 ? id : null),
    queryFn: () => fetchMyQuote(id),
    enabled: Number.isFinite(id) && id > 0,
  });
  const quote = quoteQuery.data ?? null;
  const statusMutation = useMutation({
    mutationFn: (action: 'accept' | 'refuse') =>
      action === 'accept' ? acceptMyQuote(id) : refuseMyQuote(id),
    onSuccess: (response, action) => {
      queryClient.setQueryData(quoteQueryKeys.mineDetail(id), response.data);
      void queryClient.invalidateQueries({ queryKey: quoteQueryKeys.mine() });
      toast.show(
        response.message ??
          (action === 'accept' ? 'Le devis a bien été accepté.' : 'Le devis a bien été refusé.'),
        { variant: 'success' },
      );
    },
    onError: (e) => {
      toast.show(getHttpErrorMessage(e, 'Impossible de mettre à jour le devis.'), {
        variant: 'error',
      });
    },
    onSettled: () => setUpdatingStatus(null),
  });
  const handleDownload = async () => {
    if (!quote) return;
    setDownloading(true);
    try {
      downloadBlob(await generateMyQuotePdf(quote.id), `${quote.number}.pdf`);
    } catch (e) {
      toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), {
        variant: 'error',
      });
    } finally {
      setDownloading(false);
    }
  };
  const handleStatusAction = async (action: 'accept' | 'refuse') => {
    if (!quote) return;
    setUpdatingStatus(action);
    statusMutation.mutate(action);
  };
  return {
    quote,
    loading: quoteQuery.isLoading,
    error: !Number.isFinite(id) || id <= 0
      ? 'Devis introuvable.'
      : quoteQuery.error
        ? getHttpErrorMessage(quoteQuery.error, 'Impossible de charger ce devis.')
        : null,
    downloading,
    updatingStatus,
    handleDownload,
    handleStatusAction,
  };
};
