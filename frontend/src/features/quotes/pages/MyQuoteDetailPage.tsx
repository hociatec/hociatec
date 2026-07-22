import { getHttpErrorMessage, getHttpErrorMessageAsync } from '@/shared/lib/httpClient';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { acceptMyQuote, fetchMyQuote, formatQuoteStatus, generateMyQuotePdf, refuseMyQuote, type QuoteDto } from '@/features/quotes/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { useToast } from '@/shared/components/ui/toast';
import { formatDateInputForDisplay, formatEuroCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

export const MyQuoteDetailPage = () => {
  const { quoteId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const [quote, setQuote] = useState<QuoteDto | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);
  const [updatingStatus, setUpdatingStatus] = useState<'accept' | 'refuse' | null>(null);

  useDocumentTitle(quote ? `Devis ${quote.number}` : 'Consulter le devis');

  useEffect(() => {
    const id = Number(quoteId);

    if (!id) {
      setError('Devis introuvable.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    void fetchMyQuote(id)
      .then((result) => setQuote(result))
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger ce devis.')))
      .finally(() => setLoading(false));
  }, [quoteId]);

  const handleDownload = async () => {
    if (!quote) return;

    setDownloading(true);
    try {
      const blob = await generateMyQuotePdf(quote.id);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${quote.number}.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (e) {
      toast.show(await getHttpErrorMessageAsync(e, 'Impossible de télécharger le devis.'), { variant: 'error' });
    } finally {
      setDownloading(false);
    }
  };

  const handleStatusAction = async (action: 'accept' | 'refuse') => {
    if (!quote) return;

    setUpdatingStatus(action);
    try {
      const updated = action === 'accept' ? await acceptMyQuote(quote.id) : await refuseMyQuote(quote.id);
      setQuote(updated);
      toast.show(action === 'accept' ? 'Devis accepté.' : 'Devis refusé.', { variant: 'success' });
    } catch (e) {
      toast.show(getHttpErrorMessage(e, 'Impossible de mettre à jour le devis.'), { variant: 'error' });
    } finally {
      setUpdatingStatus(null);
    }
  };

  const quoteStatus = quote?.statusCode ?? quote?.status;
  const canAnswerQuote = quoteStatus === 'sent' && !quote?.convertedOrder;

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <div className="quote-detail-header">
          <div>
            <Link to="/quotes/me" className="muted">
              Retour à mes devis
            </Link>
            <h1 className="quote-detail-title">Consulter le devis</h1>
          </div>
          <div className="quote-actions-row">
            <button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/quotes/me')}>
              Retour
            </button>
            <button
              type="button"
              className="register-form__submit"
              onClick={() => void handleDownload()}
              disabled={downloading || !quote}
            >
              {downloading ? 'Téléchargement...' : 'Télécharger'}
            </button>
            {canAnswerQuote ? (
              <>
                <button
                  type="button"
                  className="register-form__submit"
                  onClick={() => void handleStatusAction('accept')}
                  disabled={updatingStatus !== null}
                >
                  {updatingStatus === 'accept' ? 'Acceptation...' : 'Accepter'}
                </button>
                <button
                  type="button"
                  className="catalog-admin-actions__edit"
                  onClick={() => void handleStatusAction('refuse')}
                  disabled={updatingStatus !== null}
                >
                  {updatingStatus === 'refuse' ? 'Refus...' : 'Refuser'}
                </button>
              </>
            ) : null}
          </div>
        </div>

        {loading ? <LoadingState>Chargement du devis...</LoadingState> : null}
        {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}

        {quote ? (
          <div className="quote-detail-stack">
            <section className="catalog-form-section">
              <div className="quote-summary-grid">
                <div>
                  <div className="muted">Numéro</div>
                  <div className="quote-kpi-value">{quote.number}</div>
                </div>
                <div>
                  <div className="muted">Statut</div>
                  <div className="quote-strong">{quote.statusLabel ?? formatQuoteStatus(quoteStatus)}</div>
                  {quote.convertedOrder ? (
                    <Link to={`/orders/${quote.convertedOrder.id}`} className="underline text-sm">
                      Voir la commande {quote.convertedOrder.number}
                    </Link>
                  ) : null}
                </div>
                <div>
                  <div className="muted">Date</div>
                  <div>{formatOptionalFrenchDateTime(quote.createdAt)}</div>
                </div>
                <div>
                  <div className="muted">Validité</div>
                  <div>
                    {formatDateInputForDisplay(quote.validFrom)} au {formatDateInputForDisplay(quote.validUntil)}
                  </div>
                </div>
                <div>
                  <div className="muted">Total TTC</div>
                  <div className="quote-kpi-value">{formatEuroCents(quote?.totals?.ttc ?? 0)}</div>
                </div>
              </div>
            </section>

            <section className="quote-two-column-grid">
              <div className="catalog-form-section">
                <div className="catalog-form-section__header">
                  <h2 className="catalog-form-section__title">Informations client</h2>
                </div>
                <div>{quote?.customer?.name || '-'}</div>
                <div>{quote?.customer?.email || '-'}</div>
                {quote?.customer?.company ? <div>{quote.customer.company}</div> : null}
                <div className="quote-preline">{quote?.customer?.address || '-'}</div>
              </div>

              <div className="catalog-form-section">
                <div className="catalog-form-section__header">
                  <h2 className="catalog-form-section__title">Total</h2>
                </div>
                <div>Total HT : {formatEuroCents(quote?.totals?.ht ?? 0)}</div>
                <div>TVA : {formatEuroCents(quote?.totals?.vat ?? 0)}</div>
                <div>Total TTC : {formatEuroCents(quote?.totals?.ttc ?? 0)}</div>
                {quote.discountCents ? <div>Remise : {formatEuroCents(quote.discountCents)}</div> : null}
                {quote.shippingCents ? <div>Frais : {formatEuroCents(quote.shippingCents)}</div> : null}
              </div>
            </section>

            <section className="catalog-form-section">
              <div className="catalog-form-section__header">
                <h2 className="catalog-form-section__title">Articles</h2>
              </div>
              <div className="quote-table-scroll">
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
                    {(quote.items ?? []).map((item) => (
                      <tr key={item.id ?? `${item.name}-${item.quantity}`}>
                        <td className="quote-strong">{item.name}</td>
                        <td>{item.description || '-'}</td>
                        <td>
                          {item.quantity}
                          {item.unit ? ` ${item.unit}` : ''}
                        </td>
                        <td>{formatEuroCents(item.unitPriceCents ?? 0)}</td>
                        <td>{item.vatRate ?? 0}%</td>
                        <td>{formatEuroCents(item?.lineTotals?.ttc ?? 0)}</td>
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
                <div className="quote-preline">{quote.conditions}</div>
              </section>
            ) : null}
          </div>
        ) : null}
      </div>
    </SiteLayout>
  );
};
