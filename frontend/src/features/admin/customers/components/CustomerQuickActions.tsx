import { Link } from 'react-router-dom';

import { type AdminCustomerDetailDto } from '@/features/admin/customers/api';
import { type OrderDto } from '@/features/orders/api';
import { normalizePhoneLink } from './customerDetailShared';

export const CustomerQuickActions = ({
  customer,
  emailOpen,
  latestOrder,
  onToggleEmail,
}: {
  customer: AdminCustomerDetailDto;
  emailOpen: boolean;
  latestOrder: OrderDto | null;
  onToggleEmail: () => void;
}) => (
  <section className="rounded-2xl border border-brand-100 p-4">
    <div className="flex flex-wrap gap-3">
      <button
        type="button"
        className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
        onClick={onToggleEmail}
      >
        {emailOpen ? 'Fermer l’e-mail' : 'Envoyer un e-mail'}
      </button>
      {customer.phoneNumber ? (
        <a
          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
          href={`tel:${normalizePhoneLink(customer.phoneNumber)}`}
        >
          Appeler le client
        </a>
      ) : null}
      {latestOrder ? (
        <Link
          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
          to={`/admin/orders/${latestOrder.id}`}
        >
          Ouvrir la dernière commande
        </Link>
      ) : null}
      {customer.ordersCount > 0 ? (
        <Link
          className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
          to={`/admin/orders?search=${encodeURIComponent(customer.email)}`}
        >
          Rechercher ses commandes
        </Link>
      ) : null}
      <Link
        className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
        to={`/admin/customers/${customer.id}/vouchers/new`}
      >
        Gérer les bons de réduction
      </Link>
    </div>
  </section>
);
