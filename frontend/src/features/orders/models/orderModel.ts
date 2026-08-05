import { buildOrderInvoiceFilename } from '@/features/orders/orderApiShared';
import type { OrderDto } from '@/features/orders/orderTypes';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';
import type { OrderStatus } from '@/shared/contracts/statuses';
import { type OrderId, toOrderId } from '@/shared/types/ids';

export type Order = {
  id: OrderId;
  number: string;
  status: OrderStatus;
  statusLabel: string;
  createdAt: string;
  totalPriceCents: number;
  pendingReviewsCount: number;
  invoiceFilename: string;
};

export type OrderViewModel = Order & {
  detailPath: string;
  createdAtLabel: string;
  totalPriceLabel: string;
  pendingReviewsLabel: string;
  canPay: boolean;
  canCancel: boolean;
  canDownloadInvoice: boolean;
};

export const mapOrderDtoToOrder = (order: OrderDto): Order => ({
  id: toOrderId(order.id),
  number: order.number,
  status: order.status,
  statusLabel: order.statusLabel ?? order.status,
  createdAt: order.createdAt,
  totalPriceCents: order.totalPriceCents,
  pendingReviewsCount: order.pendingReviewsCount ?? 0,
  invoiceFilename: buildOrderInvoiceFilename(order),
});

export const mapOrderToViewModel = (order: Order): OrderViewModel => ({
  ...order,
  detailPath: `/orders/${order.id}`,
  createdAtLabel: formatOptionalFrenchDate(order.createdAt),
  totalPriceLabel: formatEuroCents(order.totalPriceCents),
  pendingReviewsLabel:
    order.pendingReviewsCount > 0
      ? `${order.pendingReviewsCount} produit${order.pendingReviewsCount > 1 ? 's' : ''}`
      : 'Aucun',
  canPay: order.status === 'pending',
  canCancel: order.status === 'pending',
  canDownloadInvoice: order.status !== 'pending' && order.status !== 'cancelled',
});

export const mapOrderDtoToViewModel = (order: OrderDto): OrderViewModel =>
  mapOrderToViewModel(mapOrderDtoToOrder(order));
