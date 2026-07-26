import { type AdminCustomerDetailDto } from '@/features/admin/customers/api';
import { formatEuroCents, formatOptionalFrenchDate } from '@/shared/lib/formatters';

export const CustomerSummaryCards = ({ customer }: { customer: AdminCustomerDetailDto }) => (
  <section className="grid gap-4 lg:grid-cols-4">
    <div className="rounded-2xl border border-brand-100 p-4">
      <div className="text-sm text-stone-500">Client</div>
      <div className="mt-2 text-lg font-semibold text-brand-900">{customer.fullName}</div>
      <div className="text-sm text-stone-600">{customer.email}</div>
      <div className="text-sm text-stone-600">{customer.phoneNumber}</div>
    </div>
    <div className="rounded-2xl border border-brand-100 p-4">
      <div className="text-sm text-stone-500">Commandes</div>
      <div className="mt-2 text-2xl font-semibold text-brand-900">{customer.ordersCount}</div>
      <div className="text-sm text-stone-600">Dernière: {customer.lastOrderNumber ?? 'Aucune'}</div>
    </div>
    <div className="rounded-2xl border border-brand-100 p-4">
      <div className="text-sm text-stone-500">Total dépensé</div>
      <div className="mt-2 text-2xl font-semibold text-brand-900">
        {formatEuroCents(customer.totalSpentCents)}
      </div>
      <div className="text-sm text-stone-600">
        Inscrit le {formatOptionalFrenchDate(customer.createdAt)}
      </div>
    </div>
    <div className="rounded-2xl border border-brand-100 p-4">
      <div className="text-sm text-stone-500">Compte</div>
      <div className="mt-2 text-lg font-semibold text-brand-900">
        {customer.isVerified ? 'Vérifié' : 'Non vérifié'}
      </div>
      <div className="text-sm text-stone-600">
        {customer.lastOrderAt
          ? `Dernière activité ${formatOptionalFrenchDate(customer.lastOrderAt)}`
          : 'Aucune activité de commande'}
      </div>
    </div>
  </section>
);
