import type { OrderDto } from './orderTypes';

export const downloadBlob = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  window.URL.revokeObjectURL(url);
};

const normalizeFilenamePart = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');

export const buildOrderInvoiceFilename = (order: Pick<OrderDto, 'number' | 'createdAt' | 'shipping' | 'customerDisplayName'>) => {
  const date = new Date(order.createdAt);
  const datePart = Number.isNaN(date.getTime())
    ? 'date-inconnue'
    : `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const clientName = normalizeFilenamePart(order.customerDisplayName || order.shipping?.name || 'client');
  const orderNumber = normalizeFilenamePart(order.number || 'commande');

  return `facture-${datePart}-${clientName}-${orderNumber}`;
};
