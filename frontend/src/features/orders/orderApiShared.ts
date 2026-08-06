import type { OrderDto } from './orderTypes';
import { slugify } from '@/shared/lib/slugify';

export { downloadBlob } from '@/shared/lib/downloadFile';

export const buildOrderInvoiceFilename = (
  order: Pick<OrderDto, 'number' | 'createdAt' | 'shipping' | 'customerDisplayName'>,
) => {
  const date = new Date(order.createdAt);
  const datePart = Number.isNaN(date.getTime())
    ? 'date-inconnue'
    : `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  const clientName = slugify(
    order.customerDisplayName || order.shipping?.name || 'client',
  );
  const orderNumber = slugify(order.number || 'commande');

  return `facture-${datePart}-${clientName}-${orderNumber}`;
};
