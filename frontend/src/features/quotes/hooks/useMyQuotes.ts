import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { deleteMyQuote, fetchMyQuotes, generateMyQuotePdf } from '../api/quotesApi';
import type { QuoteDto } from '../types/quoteTypes';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { useToast } from '@/shared/components/ui/toast';
import { quoteQueryKeys } from '@/shared/lib/queryKeys';

export const useMyQuotes = () => {
  const [page, setPage] = useState(1);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const toast = useToast();
  const queryClient = useQueryClient();
  const quotesQuery = useQuery({
    queryKey: [...quoteQueryKeys.mine(), { page }],
    queryFn: () => fetchMyQuotes(page, 10),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteMyQuote,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: quoteQueryKeys.mine() });
      toast.show(response.message ?? 'Le devis a bien été supprimé.', { variant: 'success' });
      cancelDelete();
    },
    onError: (e) => {
      toast.show(getHttpErrorMessage(e, 'Impossible de supprimer le devis.'), { variant: 'error' });
    },
  });
  const requestDelete = (id: number) => {
    setDeletingId(id);
    setConfirmOpen(true);
  };
  const cancelDelete = () => {
    setConfirmOpen(false);
    setDeletingId(null);
  };
  const confirmDelete = async () => {
    if (!deletingId) return;
    deleteMutation.mutate(deletingId);
  };
  const download = async (quote: QuoteDto) => {
    setDownloadingId(quote.id);
    try {
      downloadBlob(await generateMyQuotePdf(quote.id), `${quote.number}.pdf`);
    } catch (e) {
      toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), {
        variant: 'error',
      });
    } finally {
      setDownloadingId(null);
    }
  };
  return {
    items: quotesQuery.data?.items ?? [],
    pagination: quotesQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    setPage,
    loading: quotesQuery.isLoading,
    error: quotesQuery.error ? getHttpErrorMessage(quotesQuery.error, 'Impossible de charger vos devis.') : null,
    deletingId,
    downloadingId,
    confirmOpen,
    requestDelete,
    cancelDelete,
    confirmDelete,
    download,
  };
};
