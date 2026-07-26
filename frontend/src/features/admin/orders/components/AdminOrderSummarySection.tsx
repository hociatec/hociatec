import type { OrderDto, OrderProcessingDto } from '@/features/orders/api';
import { formatEuroCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type AdminOrderSummarySectionProps = {
  order: OrderDto;
  processing: OrderProcessingDto;
  canDownloadInvoice: boolean;
  regenerateInvoice: () => Promise<void>;
  resendOrderEmail: () => Promise<void>;
  resendStatusEmail: () => Promise<void>;
  downloadInvoicePdf: () => Promise<void>;
  downloadInvoiceXml: () => Promise<void>;
};

const formatDateTime = (value?: string | null) =>
  value ? formatOptionalFrenchDateTime(value) : 'Non envoyé';

export const AdminOrderSummarySection = ({
  order,
  processing,
  canDownloadInvoice,
  regenerateInvoice,
  resendOrderEmail,
  resendStatusEmail,
  downloadInvoicePdf,
  downloadInvoiceXml,
}: AdminOrderSummarySectionProps) => (
  <section className="overflow-hidden rounded-xl border border-brand-100 bg-white shadow-sm">
    <div className="border-b border-brand-100 bg-brand-900 px-6 py-5 text-white">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Commande</p>
          <h2 className="mt-1 text-2xl font-semibold">{order.number}</h2>
          <p className="mt-2 text-sm text-stone-500">
            Créée le {formatOptionalFrenchDateTime(order.createdAt)} pour{' '}
            {order.customerDisplayName || order.invoice?.billingName || order.shipping.name || 'Client inconnu'}.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <span className="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-white">
            {order.statusLabel}
          </span>
          <span className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-brand-900">
            {formatEuroCents(order.totalPriceCents)}
          </span>
        </div>
      </div>
    </div>

    <div className="grid gap-4 px-6 py-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
      <div className="rounded-xl border border-brand-100 p-5">
        <div className="text-sm font-semibold text-brand-900">Client et facturation</div>
        <div className="mt-3 font-semibold text-brand-900">
          {order.customerDisplayName || order.invoice?.billingName || order.shipping.name || 'Client inconnu'}
        </div>
        {order.invoice?.billingCompany ? (
          <div className="mt-1 text-sm text-stone-600">{order.invoice.billingCompany}</div>
        ) : null}
        {order.invoice?.billingEmail ? (
          <div className="text-sm text-stone-600">{order.invoice.billingEmail}</div>
        ) : null}
        <div className="mt-4 grid gap-3 text-sm text-stone-600">
          <div>
            <span className="font-medium text-brand-900">Statut</span> :{' '}
            {order.statusLabel}
          </div>
          <div>
            <span className="font-medium text-brand-900">Date</span> :{' '}
            {formatOptionalFrenchDateTime(order.createdAt)}
          </div>
          {order.invoice?.number ? (
            <div>
              <span className="font-medium text-brand-900">Facture</span> : {order.invoice.number}
            </div>
          ) : null}
          {order.payment ? (
            <div>
              <span className="font-medium text-brand-900">Paiement</span> :{' '}
            {order.payment.statusLabel}
            </div>
          ) : null}
        </div>
      </div>

      <div className="rounded-xl border border-brand-100 p-5">
        <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
          <div>
            <div className="text-sm font-semibold text-brand-900">Traitements automatiques</div>
            <p className="mt-1 text-sm text-stone-500">
              Vérification rapide de la facture et des e-mails liés à la commande.
            </p>
          </div>
        </div>
        {order.hasIssues && (order.issueReasons?.length ?? 0) > 0 ? (
          <div className="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div className="text-sm font-semibold text-amber-950">Anomalies détectées</div>
            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-900">
              {order.issueReasons?.map((reason) => <li key={reason}>{reason}</li>)}
            </ul>
          </div>
        ) : null}
        <ul className="mt-4 space-y-2 text-sm text-stone-700">
          <li>Facture PDF: {processing.invoicePdfGenerated ? 'générée' : 'manquante'}</li>
          <li>Facture XML: {processing.invoiceXmlGenerated ? 'générée' : 'manquante'}</li>
          <li>Email commande: {formatDateTime(processing.orderCreatedEmailSentAt)}</li>
          <li>Email livraison: {formatDateTime(processing.statusDeliveredEmailSentAt)}</li>
          <li>Email annulation: {formatDateTime(processing.statusCancelledEmailSentAt)}</li>
        </ul>
        <div className="mt-5 flex flex-wrap gap-3 text-sm">
          <button
            type="button"
            className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 font-semibold text-stone-700 transition hover:border-brand-600"
            onClick={() => void regenerateInvoice()}
          >
            Regénérer la facture
          </button>
          <button
            type="button"
            className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 font-semibold text-stone-700 transition hover:border-brand-600"
            onClick={() => void resendOrderEmail()}
          >
            Renvoyer email commande
          </button>
          {order.status === 'delivered' || order.status === 'cancelled' ? (
            <button
              type="button"
              className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 font-semibold text-stone-700 transition hover:border-brand-600"
              onClick={() => void resendStatusEmail()}
            >
              Renvoyer email statut
            </button>
          ) : null}
        </div>
        {order.invoice?.number ? (
          <div className="mt-4 flex flex-wrap gap-3 text-sm">
            <button
              type="button"
              className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50"
              onClick={() => void downloadInvoicePdf()}
              disabled={!canDownloadInvoice}
              title={!canDownloadInvoice ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}
            >
              Télécharger la facture PDF
            </button>
            <button
              type="button"
              className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
              onClick={() => void downloadInvoiceXml()}
              disabled={!canDownloadInvoice}
              title={!canDownloadInvoice ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}
            >
              Télécharger la facture XML
            </button>
          </div>
        ) : null}
      </div>
    </div>
  </section>
);
