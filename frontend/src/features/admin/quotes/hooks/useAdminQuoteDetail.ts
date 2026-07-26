import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
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
export const useAdminQuoteDetail = () => {
  const { quoteId } = useParams();
  const navigate = useNavigate();
  const prompt = usePrompt();
  const toast = useToast();
  const [quote, setQuote] = useState<QuoteDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  useEffect(() => {
    const id = Number(quoteId);
    if (!id) {
      setError('Devis introuvable.');
      setLoading(false);
      return;
    }
    setLoading(true);
    void fetchAdminQuote(id)
      .then(setQuote)
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger le devis.')))
      .finally(() => setLoading(false));
  }, [quoteId]);
  const refresh = async () => {
    if (!quote) return;
    setQuote(await fetchAdminQuote(quote.id));
  };
  const handleDownload = async () => {
    if (!quote) return;
    setDownloading(true);
    try {
      downloadBlob(await generateAdminQuotePdf(quote.id), `${quote.number}.pdf`);
    } catch (e) {
      toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), {
        variant: 'error',
      });
    } finally {
      setDownloading(false);
    }
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
    setActionLoading('send');
    try {
      const response = await sendAdminQuoteEmail(quote.id, to);
      await refresh();
      toast.show(getHttpErrorMessage(response, 'Devis envoyé.'), {
        variant: 'success',
      });
    } catch (e) {
      toast.show(getHttpErrorMessage(e, 'Envoi impossible.'), {
        variant: 'error',
      });
    } finally {
      setActionLoading(null);
    }
  };
  const handleUpdateStatus = async (status: QuoteStatus) => {
    if (!quote) return;
    setActionLoading(status);
    try {
      setQuote(await updateAdminQuoteStatus(quote.id, status));
      toast.show('Statut mis à jour.', { variant: 'success' });
    } catch (e) {
      toast.show(getHttpErrorMessage(e, 'Mise à jour impossible.'), {
        variant: 'error',
      });
    } finally {
      setActionLoading(null);
    }
  };
  const handleConvertToOrder = async () => {
    if (!quote) return;
    setActionLoading('convert');
    try {
      const response = await convertAdminQuoteToOrder(quote.id);
      const order = response?.order;
      toast.show(order ? `Commande ${order.number} créée.` : 'Commande créée.', {
        variant: 'success',
      });
      if (order?.id) navigate(`/admin/orders/${order.id}`);
      else await refresh();
    } catch (e) {
      toast.show(getHttpErrorMessage(e, 'Conversion impossible.'), {
        variant: 'error',
      });
    } finally {
      setActionLoading(null);
    }
  };
  return {
    quote,
    loading,
    error,
    downloading,
    actionLoading,
    handleDownload,
    handleSendEmail,
    handleUpdateStatus,
    handleConvertToOrder,
    navigate,
  };
};
