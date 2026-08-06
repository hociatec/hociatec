import type { OrderDto } from '@/features/orders/publicApi';

export type OrderStatus = 'pending' | 'confirmed' | 'delivered' | 'cancelled';
export type OrderSortKey = 'newest' | 'oldest' | 'amount_desc' | 'amount_asc' | 'customer_asc';
export type OrderHealthFilter = 'all' | 'issues';
export type OrderStatusFilter = 'all' | OrderStatus;

export const getNextOrderStatuses = (order: Pick<OrderDto, 'allowedNextStatuses'>) =>
  order.allowedNextStatuses;
export const getOrderCustomerLabel = (order: OrderDto) =>
  order.customerDisplayName ||
  order.invoice?.billingName ||
  order.shipping?.name ||
  order.invoice?.billingEmail ||
  'Client inconnu';
export const getOrderPaymentLabel = (order: OrderDto) =>
  order.payment ? (order.payment.statusLabel ?? order.payment.status) : 'Aucun';