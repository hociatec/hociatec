import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { acceptMyQuote, fetchMyQuote, generateMyQuotePdf, refuseMyQuote, type QuoteDto } from '../api/quotesApi';
import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { downloadBlob } from '@/shared/lib/downloadFile';
import { useToast } from '@/shared/components/ui/toast';

export const useMyQuoteDetail = () => {
  const { quoteId } = useParams();
  const [quote, setQuote] = useState<QuoteDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);
  const [updatingStatus, setUpdatingStatus] = useState<'accept' | 'refuse' | null>(null);
  const toast = useToast();
  useEffect(() => {
    const id = Number(quoteId);
    if (!id) { setError('Devis introuvable.'); setLoading(false); return; }
    setLoading(true); setError(null);
    void fetchMyQuote(id).then(setQuote).catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger ce devis.'))).finally(() => setLoading(false));
  }, [quoteId]);
  const handleDownload = async () => {
    if (!quote) return;
    setDownloading(true);
    try { downloadBlob(await generateMyQuotePdf(quote.id), `${quote.number}.pdf`); }
    catch (e) { toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), { variant: 'error' }); }
    finally { setDownloading(false); }
  };
  const handleStatusAction = async (action: 'accept' | 'refuse') => {
    if (!quote) return;
    setUpdatingStatus(action);
    try { setQuote(action === 'accept' ? await acceptMyQuote(quote.id) : await refuseMyQuote(quote.id)); toast.show(action === 'accept' ? 'Devis accepté.' : 'Devis refusé.', { variant: 'success' }); }
    catch (e) { toast.show(getHttpErrorMessage(e, 'Impossible de mettre à jour le devis.'), { variant: 'error' }); }
    finally { setUpdatingStatus(null); }
  };
  return { quote, loading, error, downloading, updatingStatus, handleDownload, handleStatusAction };
};
