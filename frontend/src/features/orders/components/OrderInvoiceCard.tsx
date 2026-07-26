import type { OrderDto } from '@/features/orders/api';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';

type Props = {
  invoice: NonNullable<OrderDto['invoice']>;
  canDownloadInvoice: boolean;
  onDownloadPdf: () => void;
  onDownloadXml: () => void;
};

export const OrderInvoiceCard = ({ invoice, canDownloadInvoice, onDownloadPdf, onDownloadXml }: Props) => (
  <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
    <div className="mb-3 flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 className="font-semibold">Facture</h2>
        {invoice.number ? <div className="text-sm text-stone-600">{invoice.number}</div> : null}
        {invoice.issuedAt ? <div className="text-sm text-stone-500">Émise le {formatOptionalFrenchDate(invoice.issuedAt)}</div> : null}
        <div className="text-sm text-stone-500">Format électronique: {invoice.electronicFormat}</div>
      </div>
      <div className="flex flex-wrap gap-2">
        <button type="button" className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50" onClick={onDownloadPdf} disabled={!canDownloadInvoice} title={!canDownloadInvoice ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}>Télécharger la facture PDF</button>
        <button type="button" className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600 disabled:cursor-not-allowed disabled:opacity-50" onClick={onDownloadXml} disabled={!canDownloadInvoice} title={!canDownloadInvoice ? 'La facture est disponible uniquement pour une commande réglée non annulée.' : undefined}>Télécharger le XML</button>
      </div>
    </div>
    <div className="text-sm">
      <div>{invoice.billingName}</div>
      {invoice.billingCompany ? <div>{invoice.billingCompany}</div> : null}
      {invoice.billingCompanySiren ? <div>SIREN : {invoice.billingCompanySiren}</div> : null}
      {invoice.billingCompanyVatNumber ? <div>TVA : {invoice.billingCompanyVatNumber}</div> : null}
      {invoice.purchaseOrderNumber ? <div>Bon de commande : {invoice.purchaseOrderNumber}</div> : null}
      <div>{invoice.billingAddress}</div>
      <div>{invoice.billingPostalCode} {invoice.billingCity}</div>
      {invoice.billingEmail ? <div>{invoice.billingEmail}</div> : null}
    </div>
  </div>
);
