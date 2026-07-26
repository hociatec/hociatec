import { useEffect, useState } from 'react';
import { deleteMyQuote, fetchMyQuotes, generateMyQuotePdf, type QuoteDto } from '../api/quotesApi';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { useToast } from '@/shared/components/ui/toast';

export const useMyQuotes = () => {
  const [items, setItems] = useState<QuoteDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const toast = useToast();
  useEffect(() => { void fetchMyQuotes().then(setItems).catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger vos devis.'))).finally(() => setLoading(false)); }, []);
  const requestDelete = (id: number) => { setDeletingId(id); setConfirmOpen(true); };
  const cancelDelete = () => { setConfirmOpen(false); setDeletingId(null); };
  const confirmDelete = async () => {
    if (!deletingId) return;
    try { await deleteMyQuote(deletingId); setItems((list) => list.filter((quote) => quote.id !== deletingId)); toast.show('Devis supprimé.', { variant: 'success' }); }
    catch (e) { toast.show(getHttpErrorMessage(e, 'Impossible de supprimer le devis.'), { variant: 'error' }); }
    finally { cancelDelete(); }
  };
  const download = async (quote: QuoteDto) => {
    setDownloadingId(quote.id);
    try { downloadBlob(await generateMyQuotePdf(quote.id), `${quote.number}.pdf`); }
    catch (e) { toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), { variant: 'error' }); }
    finally { setDownloadingId(null); }
  };
  return { items, loading, error, deletingId, downloadingId, confirmOpen, requestDelete, cancelDelete, confirmDelete, download };
};
