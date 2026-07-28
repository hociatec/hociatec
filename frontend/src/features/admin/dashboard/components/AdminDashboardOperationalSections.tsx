import { Link } from 'react-router';
import { ArrowRight, CircleAlert, CircleCheckBig } from 'lucide-react';

import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';

export const AdminDashboardOperationalSections = ({
  dashboard,
}: {
  dashboard: AdminDashboardDto;
}) => (
  <>
    <OrdersCustomersSection dashboard={dashboard} />
    <PaymentsListsSection dashboard={dashboard} />
    <RecentEventsSection dashboard={dashboard} />
  </>
);

const OrdersCustomersSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="grid gap-6 xl:grid-cols-3">
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6 xl:col-span-2">
      <PanelTitle
        title="Suivi commandes"
        helper="Dernières commandes enregistrées."
        to="/admin/orders"
        linkLabel="Toutes les commandes"
      />
      <div className="space-y-3">
        {dashboard.recentOrders.map((order) => (
          <article
            key={order.id}
            className="flex flex-col gap-2 rounded-2xl bg-brand-900/40 p-4 md:flex-row md:items-center md:justify-between"
          >
            <div>
              <div className="font-semibold text-white">{order.number}</div>
              <div className="text-sm text-stone-500">
                {order.customerDisplayName} · {formatFrenchDateTime(order.createdAt)}
              </div>
            </div>
            <div className="flex flex-col items-start gap-2 md:items-end">
              <div className="text-sm font-semibold text-white">
                {formatEuroCents(order.totalPriceCents)}
              </div>
              <div className="text-xs uppercase tracking-wide text-stone-400">
                {order.statusLabel}
              </div>
              <DashboardCardAction to={`/admin/orders/${order.id}`} label="Voir" />
            </div>
          </article>
        ))}
      </div>
    </div>
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
      <PanelTitle
        title="Clients à suivre"
        helper="Top clients par valeur."
        to="/admin/customers"
        linkLabel="Tous les clients"
      />
      <div className="space-y-3">
        {dashboard.topCustomers.map((customer) => (
          <article key={customer.id} className="rounded-2xl bg-brand-900/40 p-4">
            <div className="font-semibold text-white">
              {customer.firstName} {customer.lastName}
            </div>
            <div className="text-sm text-stone-500">{customer.email}</div>
            <div className="mt-2 text-sm text-stone-200">
              {customer.ordersCount} commande{customer.ordersCount > 1 ? 's' : ''} ·{' '}
              {formatEuroCents(customer.totalSpentCents)}
            </div>
            <DashboardCardAction
              to={`/admin/customers/${customer.id}`}
              label="Ouvrir"
              className="mt-3"
            />
          </article>
        ))}
      </div>
    </div>
  </section>
);

const PaymentsListsSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="grid gap-6 xl:grid-cols-2">
    <PaymentsPanel
      title="Paiements à surveiller"
      helper="Échecs, expirations et paiements sans commande liée."
      payments={dashboard.payments.attention}
      attention
    />
    <PaymentsPanel
      title="Derniers paiements"
      helper="Accès rapide aux dernières sessions de paiement."
      payments={dashboard.payments.recent}
    />
  </section>
);

const PaymentsPanel = ({
  attention = false,
  helper,
  payments,
  title,
}: {
  attention?: boolean;
  helper: string;
  payments: AdminDashboardDto['payments']['attention'];
  title: string;
}) => (
  <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
    <PanelTitle
      title={title}
      helper={helper}
      to="/admin/payments"
      linkLabel={attention ? 'Ouvrir le module' : 'Tous les paiements'}
    />
    <div className="space-y-3">
      {payments.length === 0 ? (
        <div className="rounded-2xl bg-brand-900/40 p-4 text-sm text-stone-500">
          Aucun paiement critique à traiter.
        </div>
      ) : (
        payments.map((payment) => (
          <article
            key={payment.id}
            className="flex flex-col gap-2 rounded-2xl bg-brand-900/40 p-4 md:flex-row md:items-center md:justify-between"
          >
            <div className="min-w-0">
              <div className="flex items-center gap-2 font-semibold text-white">
                {attention ? (
                  <CircleAlert className="h-4 w-4 text-amber-300" />
                ) : (
                  <CircleCheckBig className="h-4 w-4 text-emerald-300" />
                )}
                <span>{payment.customerFullName || payment.customerEmail}</span>
              </div>
              <div className="text-sm text-stone-500">
                {payment.statusLabel}
                {' · '}
                {attention
                  ? (payment.stripePaymentStatusLabel ??
                    payment.stripePaymentStatusLabel)
                  : payment.orderId
                    ? `Commande #${payment.orderId}`
                    : 'Aucune commande liée'}
              </div>
              {attention && (
                <div className="text-xs text-stone-400">
                  {payment.failureMessage ||
                    (payment.failureCode
                      ? payment.failureCodeLabel
                      : payment.orderId === null && payment.status === 'paid'
                        ? 'Paiement confirmé sans commande liée.'
                        : 'À contrôler')}
                </div>
              )}
            </div>
            <div className="flex flex-col items-start gap-2 md:items-end">
              <div className="text-sm font-semibold text-white">
                {formatEuroCents(payment.totalPriceCents)}
              </div>
              <div className="text-xs text-stone-400">{formatFrenchDateTime(payment.createdAt)}</div>
              <DashboardCardAction to={`/admin/payments/${payment.id}`} label="Ouvrir" />
            </div>
          </article>
        ))
      )}
    </div>
  </div>
);

const RecentEventsSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
    <div className="mb-4">
      <h3 className="text-lg font-semibold text-white">Journal récent</h3>
      <p className="text-sm text-stone-400">Derniers événements enregistrés sur les commandes.</p>
    </div>
    <div className="space-y-3">
      {dashboard.recentEvents.map((event) => (
        <article key={event.id} className="rounded-2xl bg-brand-900/40 p-4">
          <div className="text-sm font-semibold text-white">{event.order.number}</div>
          <div className="mt-1 text-sm text-stone-500">{event.message || event.type}</div>
          <div className="mt-1 text-xs text-stone-400">
            {formatFrenchDateTime(event.createdAt)}
            {event.actor?.name ? ` · ${event.actor.name}` : ''}
          </div>
          <DashboardCardAction
            to={`/admin/orders/${event.order.id}`}
            label="Voir la commande"
            className="mt-3"
          />
        </article>
      ))}
    </div>
  </section>
);

const PanelTitle = ({
  helper,
  linkLabel,
  title,
  to,
}: {
  helper: string;
  linkLabel: string;
  title: string;
  to: string;
}) => (
  <div className="mb-4 flex items-center justify-between gap-3">
    <div>
      <h3 className="text-lg font-semibold text-white">{title}</h3>
      <p className="text-sm text-stone-400">{helper}</p>
    </div>
    <Link to={to} className="text-sm font-medium text-brand-300 underline">
      {linkLabel}
    </Link>
  </div>
);

const DashboardCardAction = ({
  className = '',
  label,
  to,
}: {
  className?: string;
  label: string;
  to: string;
}) => (
  <Link
    to={to}
    className={`inline-flex items-center gap-1 rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-brand-300 transition hover:border-brand-500 hover:bg-brand-50 ${className}`}
  >
    {label}
    <ArrowRight className="h-3.5 w-3.5" />
  </Link>
);
