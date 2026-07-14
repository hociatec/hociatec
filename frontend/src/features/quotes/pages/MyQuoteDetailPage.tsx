import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import { fetchMyQuote, generateMyQuotePdf } from '@/features/quotes/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
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

export const MyQuoteDetailPage = () => {
  const { quoteId } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const [quote, setQuote] = useState<any | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);

  useDocumentTitle(quote ? `Devis ${quote.number}` : 'Détail du devis');

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
      .catch((e: any) => setError(e?.message ?? 'Impossible de charger ce devis.'))
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
    } catch (e: any) {
      toast.show(e?.message ?? 'Impossible de télécharger le devis.', { variant: 'error' });
    } finally {
      setDownloading(false);
    }
  };

  return (
    <SiteLayout>
      <div className="container mx-auto px-4 py-8">
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: '1rem',
            flexWrap: 'wrap',
            marginBottom: '1.5rem',
          }}
        >
          <div>
            <Link to="/quotes/me" className="muted">
              Retour à mes devis
            </Link>
            <h1 style={{ margin: '0.5rem 0 0', fontSize: '2rem', fontWeight: 800 }}>Détail du devis</h1>
          </div>
          <div style={{ display: 'flex', gap: '0.75rem', flexWrap: 'wrap' }}>
            <button type="button" className="catalog-admin-actions__edit" onClick={() => navigate('/quotes/me')}>
              Retour
            </button>
            <button
              type="button"
              className="register-form__submit"
              onClick={() => void handleDownload()}
              disabled={downloading || !quote}
            >
              {downloading ? 'Téléchargement...' : 'Télécharger le PDF'}
            </button>
          </div>
        </div>

        {loading ? <p>Chargement...</p> : null}
        {error ? <div className="register-form__alert">{error}</div> : null}

        {quote ? (
          <div
            style={{
              display: 'grid',
              gap: '1.5rem',
            }}
          >
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
                  <div style={{ fontWeight: 700 }}>{quote.status}</div>
                </div>
                <div>
                  <div className="muted">Date</div>
                  <div>{formatDateTime(quote.createdAt)}</div>
                </div>
                <div>
                  <div className="muted">Validité</div>
                  <div>
                    {formatDate(quote.validFrom)} au {formatDate(quote.validUntil)}
                  </div>
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
                  <h2 className="catalog-form-section__title">Informations client</h2>
                </div>
                <div>{quote?.customer?.name || '-'}</div>
                <div>{quote?.customer?.email || '-'}</div>
                {quote?.customer?.company ? <div>{quote.customer.company}</div> : null}
                <div style={{ whiteSpace: 'pre-line' }}>{quote?.customer?.address || '-'}</div>
              </div>

              <div className="catalog-form-section">
                <div className="catalog-form-section__header">
                  <h2 className="catalog-form-section__title">Totaux</h2>
                </div>
                <div>Total HT: {formatPrice(quote?.totals?.ht ?? 0)}</div>
                <div>TVA: {formatPrice(quote?.totals?.vat ?? 0)}</div>
                <div>Total TTC: {formatPrice(quote?.totals?.ttc ?? 0)}</div>
                {quote.discountCents ? <div>Remise: {formatPrice(quote.discountCents)}</div> : null}
                {quote.shippingCents ? <div>Frais: {formatPrice(quote.shippingCents)}</div> : null}
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
      </div>
    </SiteLayout>
  );
};
