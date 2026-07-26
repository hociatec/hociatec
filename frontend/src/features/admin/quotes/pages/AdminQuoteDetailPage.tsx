import { Link } from 'react-router-dom';

import { formatQuoteStatus } from '@/features/quotes/lib/quoteStatus';
import { useAdminQuoteDetail } from '../hooks/useAdminQuoteDetail';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import {
  formatDateInputForDisplay,
  formatEuroCents,
  formatOptionalFrenchDateTime,
} from '@/shared/lib/formatters';

export const AdminQuoteDetailPage = () => {
  const {
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
  } = useAdminQuoteDetail();

  useDocumentTitle(quote ? `Admin - Devis ${quote.number}` : 'Admin - Consulter le devis');

  const quoteStatus = quote?.statusCode ?? quote?.status;

  return (
    <PageContainer
      size="admin"
      title={quote ? `Devis ${quote.number}` : 'Consulter le devis'}
      headerActions={
        quote ? (
          <div className="catalog-admin-actions">
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => navigate('/admin/quotes')}
            >
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
              disabled={
                actionLoading !== null || quoteStatus === 'accepted' || !!quote.convertedOrder
              }
            >
              Accepter
            </button>
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() => void handleUpdateStatus('refused')}
              disabled={
                actionLoading !== null || quoteStatus === 'refused' || !!quote.convertedOrder
              }
            >
              Refuser
            </button>
            {quote.convertedOrder ? (
              <Link
                to={`/admin/orders/${quote.convertedOrder.id}`}
                className="catalog-admin-actions__edit"
              >
                Commande
              </Link>
            ) : (
              <button
                type="button"
                className="register-form__submit"
                onClick={() => void handleConvertToOrder()}
                disabled={
                  actionLoading !== null ||
                  quoteStatus !== 'accepted' ||
                  (quote.items ?? []).length === 0
                }
              >
                {actionLoading === 'convert' ? 'Conversion...' : 'Convertir'}
              </button>
            )}
          </div>
        ) : undefined
      }
    >
      {loading ? <LoadingState>Chargement...</LoadingState> : null}
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
                <div className="quote-strong">
                  {quote.statusLabel ?? formatQuoteStatus(quoteStatus)}
                </div>
                {quote.sentAt ? (
                  <div className="muted quote-small-muted">
                    Envoyé le {formatOptionalFrenchDateTime(quote.sentAt)}
                  </div>
                ) : null}
                {quote.convertedOrder ? (
                  <Link
                    to={`/admin/orders/${quote.convertedOrder.id}`}
                    className="underline text-sm"
                  >
                    Commande {quote.convertedOrder.number}
                  </Link>
                ) : null}
              </div>
              <div>
                <div className="muted">Créé le</div>
                <div>{formatOptionalFrenchDateTime(quote.createdAt)}</div>
              </div>
              <div>
                <div className="muted">Fin de validité</div>
                <div>{formatDateInputForDisplay(quote.validUntil)}</div>
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
                <h2 className="catalog-form-section__title">Client</h2>
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
              {quote.discountCents ? (
                <div>Remise : {formatEuroCents(quote.discountCents)}</div>
              ) : null}
              {quote.shippingCents ? (
                <div>Frais de port : {formatEuroCents(quote.shippingCents)}</div>
              ) : null}
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
    </PageContainer>
  );
};
