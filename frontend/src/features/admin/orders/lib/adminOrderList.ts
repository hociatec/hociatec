import type { OrderDto } from '@/features/orders/api';

export type OrderStatus = 'pending' | 'confirmed' | 'delivered' | 'cancelled';
export type OrderSortKey = 'newest' | 'oldest' | 'amount_desc' | 'amount_asc' | 'customer_asc';
export type OrderHealthFilter = 'all' | 'issues';
export type OrderStatusFilter = 'all' | OrderStatus;

const nextStatusMap: Record<OrderStatus, OrderStatus[]> = {
  pending: ['confirmed', 'cancelled'],
  confirmed: ['delivered'],
  delivered: [],
  cancelled: [],
};
export const getNextOrderStatuses = (status: OrderDto['status']) =>
  nextStatusMap[status as OrderStatus] ?? [];
export const getOrderCustomerLabel = (order: OrderDto) =>
  order.customerDisplayName ||
  order.invoice?.billingName ||
  order.shipping?.name ||
  order.invoice?.billingEmail ||
  'Client inconnu';
export const getOrderPaymentLabel = (order: OrderDto) =>
  order.payment ? (order.payment.statusLabel ?? order.payment.status) : 'Aucun';
export const filterAndSortAdminOrders = (
  orders: OrderDto[],
  search: string,
  sort: OrderSortKey,
) => {
  const term = search.trim().toLowerCase();
  return orders
    .filter(
      (order) =>
        !term ||
        [
          order.number,
          getOrderCustomerLabel(order),
          order.invoice?.billingEmail,
          order.invoice?.billingCompany,
          order.invoice?.number,
          order.shipping?.address,
          order.shipping?.city,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()
          .includes(term),
    )
    .sort((left, right) => {
      if (sort === 'amount_desc') return right.totalPriceCents - left.totalPriceCents;
      if (sort === 'amount_asc') return left.totalPriceCents - right.totalPriceCents;
      if (sort === 'customer_asc')
        return getOrderCustomerLabel(left).localeCompare(getOrderCustomerLabel(right), 'fr', {
          sensitivity: 'base',
        });
      const direction = sort === 'oldest' ? 1 : -1;
      return direction * (new Date(left.createdAt).getTime() - new Date(right.createdAt).getTime());
    });
};
