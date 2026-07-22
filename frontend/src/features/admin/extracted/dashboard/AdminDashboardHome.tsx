import { Link } from 'react-router-dom';
import { ArrowRight, CircleAlert, CircleCheckBig, House } from 'lucide-react';

import { type AdminDashboardDto } from '@/features/admin/customers/api';
import { formatOrderStatusFr, formatPaymentStatusFr, formatStripeFailureCodeFr, formatStripePaymentStatusFr } from '@/features/orders/api';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { type AdminDashboardSection } from '@/features/admin/extracted/dashboard/adminDashboardSections';

type AdminDashboardHomeProps = {
  dashboard: AdminDashboardDto | null;
  dashboardError: string | null;
  dashboardStatus: 'loading' | 'error' | 'success';
  defaultSection: string;
  savedMessage: string | null;
  sectionTitleMap: Record<string, string>;
  sections: AdminDashboardSection[];
  onDefaultSectionChange: (sectionId: string) => void;
};

export const AdminDashboardHome = ({
  dashboard,
  dashboardError,
  dashboardStatus,
  defaultSection,
  savedMessage,
  sectionTitleMap,
  sections,
  onDefaultSectionChange,
}: AdminDashboardHomeProps) => (
  <div className="space-y-6">
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
      <div className="mb-4 flex items-center gap-3">
        <House className="h-6 w-6 text-brand-400" />
        <h3 className="text-lg font-semibold text-white">Onglet par défaut</h3>
      </div>
      <p className="mb-4 text-sm text-stone-400">
        Choisissez l’onglet affiché automatiquement à l’ouverture du dashboard admin sur ce navigateur.
      </p>
      <select
        className="register-form__input"
        value={defaultSection}
        onChange={(event) => onDefaultSectionChange(event.target.value)}
      >
        {sections.map((item) => (
          <option key={item.id} value={item.id}>
            {item.title}
          </option>
        ))}
      </select>
      {savedMessage && <p className="mt-3 text-sm text-emerald-300">{savedMessage}</p>}
      <span className="sr-only">{sectionTitleMap[defaultSection] ?? defaultSection}</span>
    </div>

    {dashboardStatus === 'loading' && (
      <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6 text-sm text-stone-500" aria-hidden="true">
        Chargement des indicateurs...
      </div>
    )}

    {dashboardError && (
      <div className="rounded-2xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
        {dashboardError}
      </div>
    )}

    {dashboard && (
      <>
        <section className="space-y-4">
          <div>
            <h3 className="text-lg font-semibold text-white">Vue d’ensemble</h3>
            <p className="text-sm text-stone-400">Volumes, chiffre d’affaires et base clients.</p>
          </div>
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Aujourd’hui" value={dashboard.metrics.today.count} helper={formatEuroCents(dashboard.metrics.today.totalCents)} />
            <MetricCard label="Cette semaine" value={dashboard.metrics.week.count} helper={formatEuroCents(dashboard.metrics.week.totalCents)} />
            <MetricCard label="Ce mois" value={dashboard.metrics.month.count} helper={formatEuroCents(dashboard.metrics.month.totalCents)} />
            <MetricCard label="Base clients" value={dashboard.metrics.customersCount} helper={`${dashboard.topCustomers.length} clients mis en avant`} />
          </div>
        </section>

        <section className="space-y-4">
          <div>
            <h3 className="text-lg font-semibold text-white">Actions prioritaires</h3>
            <p className="text-sm text-stone-400">Les points qui demandent une action immédiate.</p>
          </div>
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <PriorityLink to="/admin/orders?status=pending" label="Commandes à traiter" value={dashboard.metrics.statusCounts.pending ?? 0} helper="Ouvrir la liste" />
            <PriorityLink to="/admin/orders?status=confirmed" label="Commandes confirmées" value={dashboard.metrics.statusCounts.confirmed ?? 0} helper="Ouvrir la liste" />
            <PriorityLink to="/admin/orders?status=delivered" label="Commandes livrées" value={dashboard.metrics.statusCounts.delivered ?? 0} helper="Ouvrir la liste" />
            <PriorityLink to="/admin/orders?health=issues" label="Incidents de traitement" value={dashboard.metrics.issuesCount} helper="Traiter maintenant" />
            <PriorityLink to="/admin/catalog/products?stock=low" label="Stocks faibles" value={dashboard.metrics.lowStockCount} helper="Voir les produits" />
            <PriorityLink
              to="/admin/operations"
              label="SAV / remboursements"
              value={(dashboard.metrics.supportOpenCount ?? 0) + (dashboard.metrics.refundsPendingCount ?? 0)}
              helper="Ouvrir exploitation"
            />
          </div>
        </section>

        <NotificationsSection dashboard={dashboard} />
        <PaymentsSummarySection dashboard={dashboard} />
        <OrdersCustomersSection dashboard={dashboard} />
        <PaymentsListsSection dashboard={dashboard} />
        <RecentEventsSection dashboard={dashboard} />
      </>
    )}
  </div>
);

const cardClass = 'rounded-2xl border border-brand-700 bg-brand-800/50 p-5';

const MetricCard = ({ label, value, helper }: { label: string; value: number; helper: string }) => (
  <div className={cardClass}>
    <div className="text-sm text-stone-400">{label}</div>
    <div className="mt-2 text-3xl font-semibold text-white">{value}</div>
    <div className="mt-1 text-sm text-stone-500">{helper}</div>
  </div>
);

const PriorityLink = ({ to, label, value, helper }: { to: string; label: string; value: number; helper: string }) => (
  <Link to={to} className="rounded-2xl border border-brand-700 bg-brand-800/50 p-5 transition hover:border-brand-500 hover:bg-brand-800/80">
    <div className="text-sm text-stone-400">{label}</div>
    <div className="mt-2 text-2xl font-semibold text-white">{value}</div>
    <div className="mt-2 text-xs text-brand-300">{helper}</div>
  </Link>
);

const NotificationsSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="space-y-4">
    <div className="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h3 className="text-lg font-semibold text-white">Notifications admin</h3>
        <p className="text-sm text-stone-400">Devis acceptés, commandes à régler, emails et paiements récents.</p>
      </div>
      <Link to="/admin/quotes" className="text-sm font-medium text-brand-300 underline">Voir les devis</Link>
    </div>
    <div className="grid gap-3 xl:grid-cols-2">
      {(dashboard.notifications ?? []).length === 0 ? (
        <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-5 text-sm text-stone-500">Aucune notification récente.</div>
      ) : (
        (dashboard.notifications ?? []).map((item) => {
          const isAction = item.severity === 'action';
          const isDanger = item.severity === 'danger';
          return (
            <Link
              key={item.id}
              to={item.to}
              className={`flex gap-3 rounded-2xl border p-4 transition hover:bg-brand-800/80 ${isDanger ? 'border-red-500/40 bg-red-500/10' : isAction ? 'border-amber-400/40 bg-amber-400/10' : 'border-brand-700 bg-brand-800/50'}`}
            >
              <div className="mt-0.5">
                {isDanger || isAction ? (
                  <CircleAlert className={`h-5 w-5 ${isDanger ? 'text-red-300' : 'text-amber-300'}`} />
                ) : (
                  <CircleCheckBig className="h-5 w-5 text-emerald-300" />
                )}
              </div>
              <div className="min-w-0 flex-1">
                <div className="font-semibold text-white">{item.title}</div>
                <div className="mt-1 truncate text-sm text-stone-500">{item.message || item.type}</div>
                <div className="mt-2 text-xs text-stone-400">{formatFrenchDateTime(item.createdAt)}</div>
              </div>
              <ArrowRight className="mt-1 h-4 w-4 flex-none text-stone-500" />
            </Link>
          );
        })
      )}
    </div>
  </section>
);

const PaymentsSummarySection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="space-y-4">
    <div>
      <h3 className="text-lg font-semibold text-white">Suivi des paiements</h3>
      <p className="text-sm text-stone-400">Vue rapide des paiements confirmés, en attente et des cas à traiter.</p>
    </div>
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
      <PriorityLink to="/admin/payments?status=paid" label="Paiements confirmés" value={dashboard.payments.statusCounts.paid ?? 0} helper="Voir les paiements" />
      <PriorityLink to="/admin/payments?status=open" label="Paiements en attente" value={dashboard.payments.statusCounts.open ?? 0} helper="Sessions ouvertes" />
      <PriorityLink to="/admin/payments?status=failed" label="Paiements échoués" value={dashboard.payments.statusCounts.failed ?? 0} helper="Analyser les refus" />
      <PriorityLink to="/admin/payments?status=expired" label="Paiements expirés" value={dashboard.payments.statusCounts.expired ?? 0} helper="Voir les sessions perdues" />
      <PriorityLink to="/admin/payments" label="Payés sans commande liée" value={dashboard.payments.paidWithoutOrderCount} helper="Contrôler les incohérences" />
    </div>
  </section>
);

const OrdersCustomersSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="grid gap-6 xl:grid-cols-3">
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6 xl:col-span-2">
      <PanelTitle title="Suivi commandes" helper="Dernières commandes enregistrées." to="/admin/orders" linkLabel="Toutes les commandes" />
      <div className="space-y-3">
        {dashboard.recentOrders.map((order) => (
          <Link key={order.id} to={`/admin/orders/${order.id}`} className="flex flex-col gap-2 rounded-2xl bg-brand-900/40 p-4 transition hover:bg-brand-900/70 md:flex-row md:items-center md:justify-between">
            <div>
              <div className="font-semibold text-white">{order.number}</div>
              <div className="text-sm text-stone-500">{order.customerDisplayName} · {formatFrenchDateTime(order.createdAt)}</div>
            </div>
            <div className="text-right">
              <div className="text-sm font-semibold text-white">{formatEuroCents(order.totalPriceCents)}</div>
              <div className="text-xs uppercase tracking-wide text-stone-400">{order.statusLabel ?? formatOrderStatusFr(order.status)}</div>
            </div>
          </Link>
        ))}
      </div>
    </div>
    <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-6">
      <PanelTitle title="Clients à suivre" helper="Top clients par valeur." to="/admin/customers" linkLabel="Tous les clients" />
      <div className="space-y-3">
        {dashboard.topCustomers.map((customer) => (
          <Link key={customer.id} to={`/admin/customers/${customer.id}`} className="block rounded-2xl bg-brand-900/40 p-4 transition hover:bg-brand-900/70">
            <div className="font-semibold text-white">{customer.firstName} {customer.lastName}</div>
            <div className="text-sm text-stone-500">{customer.email}</div>
            <div className="mt-2 text-sm text-stone-200">{customer.ordersCount} commande{customer.ordersCount > 1 ? 's' : ''} · {formatEuroCents(customer.totalSpentCents)}</div>
          </Link>
        ))}
      </div>
    </div>
  </section>
);

const PaymentsListsSection = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="grid gap-6 xl:grid-cols-2">
    <PaymentsPanel title="Paiements à surveiller" helper="Échecs, expirations et paiements sans commande liée." payments={dashboard.payments.attention} attention />
    <PaymentsPanel title="Derniers paiements" helper="Accès rapide aux dernières sessions de paiement." payments={dashboard.payments.recent} />
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
    <PanelTitle title={title} helper={helper} to="/admin/payments" linkLabel={attention ? 'Ouvrir le module' : 'Tous les paiements'} />
    <div className="space-y-3">
      {payments.length === 0 ? (
        <div className="rounded-2xl bg-brand-900/40 p-4 text-sm text-stone-500">Aucun paiement critique à traiter.</div>
      ) : payments.map((payment) => (
        <Link key={payment.id} to={`/admin/payments/${payment.id}`} className="flex flex-col gap-2 rounded-2xl bg-brand-900/40 p-4 transition hover:bg-brand-900/70 md:flex-row md:items-center md:justify-between">
          <div className="min-w-0">
            <div className="flex items-center gap-2 font-semibold text-white">
              {attention ? <CircleAlert className="h-4 w-4 text-amber-300" /> : <CircleCheckBig className="h-4 w-4 text-emerald-300" />}
              <span>{payment.customerFullName || payment.customerEmail}</span>
            </div>
            <div className="text-sm text-stone-500">
              {payment.statusLabel ?? formatPaymentStatusFr(payment.status)}
              {' · '}
              {attention ? payment.stripePaymentStatusLabel ?? formatStripePaymentStatusFr(payment.stripePaymentStatus) : payment.orderId ? `Commande #${payment.orderId}` : 'Aucune commande liée'}
            </div>
            {attention && (
              <div className="text-xs text-stone-400">
                {payment.failureMessage || (payment.failureCode ? formatStripeFailureCodeFr(payment.failureCode) : (payment.orderId === null && payment.status === 'paid' ? 'Paiement confirmé sans commande liée.' : 'À contrôler'))}
              </div>
            )}
          </div>
          <div className="text-right">
            <div className="text-sm font-semibold text-white">{formatEuroCents(payment.totalPriceCents)}</div>
            <div className="text-xs text-stone-400">{formatFrenchDateTime(payment.createdAt)}</div>
          </div>
        </Link>
      ))}
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
        <Link key={event.id} to={`/admin/orders/${event.order.id}`} className="block rounded-2xl bg-brand-900/40 p-4 transition hover:bg-brand-900/70">
          <div className="text-sm font-semibold text-white">{event.order.number}</div>
          <div className="mt-1 text-sm text-stone-500">{event.message || event.type}</div>
          <div className="mt-1 text-xs text-stone-400">
            {formatFrenchDateTime(event.createdAt)}
            {event.actor?.name ? ` · ${event.actor.name}` : ''}
          </div>
        </Link>
      ))}
    </div>
  </section>
);

const PanelTitle = ({ helper, linkLabel, title, to }: { helper: string; linkLabel: string; title: string; to: string }) => (
  <div className="mb-4 flex items-center justify-between gap-3">
    <div>
      <h3 className="text-lg font-semibold text-white">{title}</h3>
      <p className="text-sm text-stone-400">{helper}</p>
    </div>
    <Link to={to} className="text-sm font-medium text-brand-300 underline">{linkLabel}</Link>
  </div>
);
