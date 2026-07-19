import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  convertAdminQuoteToOrder,
  fetchAdminQuote,
  formatQuoteStatus,
  generateAdminQuotePdf,
  sendAdminQuoteEmail,
  updateAdminQuoteStatus,
  type QuoteStatus,
} from '@/features/quotes/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';

const formatPrice = (cents: number) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format((cents ?? 0) / 100);

const formatDateTime = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleString('fr-FR');
};

const formatDate = (value?: string | null) => {
  if (!value) return '-';

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return '-';

  return date.toLocaleDateString('fr-FR');
};

export const AdminQuoteDetailPage = () => {
  const { quoteId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const [quote, setQuote] = useState<any | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);
  const [actionLoading, setActionLoading] = useState<string | null>(null);

  useDocumentTitle(quote ? `Admin - Devis ${quote.number}` : 'Admin - Consulter le devis');

  useEffect(() => {
    const id = Number(quoteId);

    if (!id) {
      setError('Devis introuvable.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    void fetchAdminQuote(id)
      .then((result) => setQuote(result))
      .catch((e: any) => setError(e?.message ?? 'Impossible de charger ce devis.'))
      .finally(() => setLoading(false));
  }, [quoteId]);

  const handleDownload = async () => {
    if (!quote) return;

    setDownloading(true);
    try {
      const blob = await generateAdminQuotePdf(quote.id);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${quote.number}.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (e: any) {
      toast.show(e?.message ?? 'Impossible de télécharger le devis.', { variant: 'error' });
    } finally {
      setDownloading(false);
    }
  };

  const refreshQuote = async () => {
    if (!quote) return;
    const updated = await fetchAdminQuote(quote.id);
    setQuote(updated);
    return updated;
  };

  const handleSendEmail = async () => {
    if (!quote) return;

    const to = window.prompt('Destinataire (e-mail)', quote?.customer?.email ?? '') ?? undefined;
    if (to === undefined) return;

    setActionLoading('send');
    try {
      const response = await sendAdminQuoteEmail(quote.id, to);
      await refreshQuote();
      toast.show(response?.message ?? 'Devis envoyé.', { variant: 'success' });
    } catch (e: any) {
      toast.show(e?.message ?? 'Envoi impossible.', { variant: 'error' });
    } finally {
      setActionLoading(null);
    }
  };

  const handleUpdateStatus = async (status: QuoteStatus) => {
    if (!quote) return;

    setActionLoading(status);
    try {
      const updated = await updateAdminQuoteStatus(quote.id, status);
      setQuote(updated);
      toast.show('Statut mis à jour.', { variant: 'success' });
    } catch (e: any) {
      toast.show(e?.message ?? 'Mise à jour impossible.', { variant: 'error' });
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
      toast.show(order ? `Commande ${order.number} créée.` : 'Commande créée.', { variant: 'success' });
      if (order?.id) {
        navigate(`/admin/orders/${order.id}`);
      } else {
        await refreshQuote();
      }
    } catch (e: any) {
      toast.show(e?.message ?? 'Conversion impossible.', { variant: 'error' });
    } finally {
      setActionLoading(null);
    }
  };

  const quoteStatus = quote?.statusCode ?? quote?.status;

  return (
    <PageContainer
      title={quote ? `Devis ${quote.number}` : 'Consulter le devis'}
      headerActions={
        quote ? (
          <div className="catalog-admin-actions">
            <button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/admin/quotes')}>
              Retour
            </button>
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => void handleDownload()}
              disabled={downloading}
            >
              {downloading ? 'Téléchargement...' : 'Télécharger'}
            </button>
            <Link to={`/admin/quotes/${quote.id}/edit`} className="catalog-admin-actions__edit">
              Modifier
            </Link>
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => void handleSendEmail()}
              disabled={actionLoading !== null}
            >
              {actionLoading === 'send' ? 'Envoi...' : 'Envoyer'}
            </button>
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => void handleUpdateStatus('accepted')}
              disabled={actionLoading !== null || quoteStatus === 'accepted' || !!quote.convertedOrder}
            >
              Accepter
            </button>
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => void handleUpdateStatus('refused')}
              disabled={actionLoading !== null || quoteStatus === 'refused' || !!quote.convertedOrder}
            >
              Refuser
            </button>
            {quote.convertedOrder ? (
              <Link to={`/admin/orders/${quote.convertedOrder.id}`} className="catalog-admin-actions__edit">
                Commande
              </Link>
            ) : (
              <button
                type="button"
                className="register-form__submit"
                onClick={() => void handleConvertToOrder()}
                disabled={actionLoading !== null || quoteStatus !== 'accepted' || (quote.items ?? []).length === 0}
              >
                {actionLoading === 'convert' ? 'Conversion...' : 'Convertir'}
              </button>
            )}
          </div>
        ) : undefined
      }
    >
      {loading ? <p className="muted">Chargement...</p> : null}
      {error ? <div className="register-form__alert">{error}</div> : null}

      {quote ? (
        <div style={{ display: 'grid', gap: '1.5rem' }}>
          <section className="catalog-form-section">
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                gap: '1rem',
              }}
            >
              <div>
                <div className="muted">Numéro</div>
                <div style={{ fontSize: '1.2rem', fontWeight: 800 }}>{quote.number}</div>
              </div>
              <div>
                <div className="muted">Statut</div>
                <div style={{ fontWeight: 700 }}>{quote.statusLabel ?? formatQuoteStatus(quoteStatus)}</div>
                {quote.sentAt ? (
                  <div className="muted" style={{ fontSize: '0.85rem' }}>
                    Envoyé le {formatDateTime(quote.sentAt)}
                  </div>
                ) : null}
                {quote.convertedOrder ? (
                  <Link to={`/admin/orders/${quote.convertedOrder.id}`} className="underline text-sm">
                    Commande {quote.convertedOrder.number}
                  </Link>
                ) : null}
              </div>
              <div>
                <div className="muted">Créé le</div>
                <div>{formatDateTime(quote.createdAt)}</div>
              </div>
              <div>
                <div className="muted">Fin de validité</div>
                <div>{formatDate(quote.validUntil)}</div>
              </div>
              <div>
                <div className="muted">Total TTC</div>
                <div style={{ fontSize: '1.2rem', fontWeight: 800 }}>{formatPrice(quote?.totals?.ttc ?? 0)}</div>
              </div>
            </div>
          </section>

          <section
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
              gap: '1rem',
            }}
          >
            <div className="catalog-form-section">
              <div className="catalog-form-section__header">
                <h2 className="catalog-form-section__title">Client</h2>
              </div>
              <div>{quote?.customer?.name || '-'}</div>
              <div>{quote?.customer?.email || '-'}</div>
              {quote?.customer?.company ? <div>{quote.customer.company}</div> : null}
              <div style={{ whiteSpace: 'pre-line' }}>{quote?.customer?.address || '-'}</div>
            </div>

            <div className="catalog-form-section">
              <div className="catalog-form-section__header">
                <h2 className="catalog-form-section__title">Total</h2>
              </div>
              <div>Total HT : {formatPrice(quote?.totals?.ht ?? 0)}</div>
              <div>TVA : {formatPrice(quote?.totals?.vat ?? 0)}</div>
              <div>Total TTC : {formatPrice(quote?.totals?.ttc ?? 0)}</div>
              {quote.discountCents ? <div>Remise : {formatPrice(quote.discountCents)}</div> : null}
              {quote.shippingCents ? <div>Frais de port : {formatPrice(quote.shippingCents)}</div> : null}
            </div>
          </section>

          <section className="catalog-form-section">
            <div className="catalog-form-section__header">
              <h2 className="catalog-form-section__title">Articles</h2>
            </div>
            <div style={{ overflowX: 'auto' }}>
              <table className="catalog-admin-table">
                <thead>
                  <tr>
                    <th>Article</th>
                    <th>Description</th>
                    <th>Qté</th>
                    <th>PU HT</th>
                    <th>TVA</th>
                    <th>Total TTC</th>
                  </tr>
                </thead>
                <tbody>
                  {(quote.items ?? []).map((item: any) => (
                    <tr key={item.id ?? `${item.name}-${item.quantity}`}>
                      <td style={{ fontWeight: 700 }}>{item.name}</td>
                      <td>{item.description || '-'}</td>
                      <td>
                        {item.quantity}
                        {item.unit ? ` ${item.unit}` : ''}
                      </td>
                      <td>{formatPrice(item.unitPriceCents ?? 0)}</td>
                      <td>{item.vatRate ?? 0}%</td>
                      <td>{formatPrice(item?.lineTotals?.ttc ?? 0)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          {quote.conditions ? (
            <section className="catalog-form-section">
              <div className="catalog-form-section__header">
                <h2 className="catalog-form-section__title">Conditions</h2>
              </div>
              <div style={{ whiteSpace: 'pre-line' }}>{quote.conditions}</div>
            </section>
          ) : null}
        </div>
      ) : null}
    </PageContainer>
  );
};
