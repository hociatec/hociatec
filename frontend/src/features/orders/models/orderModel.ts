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

export const canPayOrderStatus = (status: OrderStatus) => status === 'pending';

export const canCancelOrderStatus = (status: OrderStatus) => status === 'pending';

export const canDownloadInvoiceForOrderStatus = (status: OrderStatus) =>
  status !== 'pending' && status !== 'cancelled';

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
  canPay: canPayOrderStatus(order.status),
  canCancel: canCancelOrderStatus(order.status),
  canDownloadInvoice: canDownloadInvoiceForOrderStatus(order.status),
});

export const mapOrderDtoToViewModel = (order: OrderDto): OrderViewModel =>
  mapOrderToViewModel(mapOrderDtoToOrder(order));
