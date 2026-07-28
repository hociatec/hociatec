import { Link } from 'react-router';

import type { OrderDto } from '@/features/orders/api';
import { AdminTableShell } from '@/shared/components/admin/AdminDataView';
import {
  formatEuroCents,
  formatOptionalFrenchDate,
  formatOptionalFrenchDateTime,
} from '@/shared/lib/formatters';
import {
  getNextOrderStatuses,
  getOrderCustomerLabel,
  getOrderPaymentLabel,
  type OrderStatus,
} from '../lib/adminOrderList';

type AdminOrdersTableProps = {
  orders: OrderDto[];
  onEditStatus: (order: OrderDto, options: OrderStatus[]) => void;
};

export const AdminOrdersTable = ({ orders, onEditStatus }: AdminOrdersTableProps) => (
  <AdminTableShell>
    <table className="catalog-admin-table">
      <thead>
        <tr>
          <th scope="col">Commande</th>
          <th scope="col">Client</th>
          <th scope="col">Date</th>
          <th scope="col">Facture</th>
          <th scope="col">Paiement</th>
          <th scope="col">Total</th>
          <th scope="col">Statut</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        {orders.map((order) => (
          <tr key={order.id}>
            <th scope="row">
              <div className="font-semibold text-brand-900">{order.number}</div>
              {order.invoice?.purchaseOrderNumber ? (
                <div className="muted">BC: {order.invoice.purchaseOrderNumber}</div>
              ) : null}
            </th>
            <td>
              <div className="font-medium text-brand-900">{getOrderCustomerLabel(order)}</div>
              {order.invoice?.billingCompany ? (
                <div className="muted">{order.invoice.billingCompany}</div>
              ) : null}
              {order.invoice?.billingEmail ? (
                <div className="muted">{order.invoice.billingEmail}</div>
              ) : null}
            </td>
            <td>
              <div>{formatOptionalFrenchDate(order.createdAt)}</div>
              <div className="muted">{formatOptionalFrenchDateTime(order.createdAt)}</div>
            </td>
            <td>
              {order.invoice?.number ? (
                <>
                  <div>{order.invoice.number}</div>
                  <div className="muted">{order.invoice.statusLabel}</div>
                </>
              ) : (
                <span className="text-xs text-stone-500">Aucune</span>
              )}
            </td>
            <td>
              <div className="font-medium text-brand-900">{getOrderPaymentLabel(order)}</div>
              {order.payment?.stripePaymentStatus ? (
                <div className="muted">
                  Stripe:{' '}
                  {order.payment.stripePaymentStatusLabel}
                </div>
              ) : null}
              {order.payment?.lastStripeEventType ? (
                <div className="muted">
                  {order.payment.lastStripeEventLabel ?? order.payment.lastStripeEventType}
                </div>
              ) : null}
            </td>
            <td>{formatEuroCents(order.totalPriceCents)}</td>
            <td>
              <div className="capitalize">
                {order.statusLabel}
              </div>
              {order.hasIssues && (order.issueReasons?.length ?? 0) > 0 ? (
                <div className="mt-2 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                  <div className="font-semibold">Anomalies détectées</div>
                  <ul className="mt-1 list-disc pl-4">
                    {order.issueReasons?.map((reason) => <li key={reason}>{reason}</li>)}
                  </ul>
                </div>
              ) : null}
            </td>
            <td>
              <div className="flex flex-wrap gap-3">
                {getNextOrderStatuses(order).length === 0 ? (
                  <span className="inline-flex items-center text-xs text-stone-500">Statut final</span>
                ) : (
                  <button
                    type="button"
                    className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                    onClick={() => onEditStatus(order, getNextOrderStatuses(order))}
                    aria-label={`Modifier le statut de la commande ${order.number}`}
                  >
                    Modifier le statut
                  </button>
                )}
                <Link
                  className="inline-flex items-center text-sm font-semibold underline"
                  to={`/admin/orders/${order.id}`}
                >
                  Détails
                </Link>
              </div>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  </AdminTableShell>
);
