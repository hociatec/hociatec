import type { OrderEventDto } from '@/features/orders/publicApi';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type AdminOrderHistorySectionProps = {
  events: OrderEventDto[];
};

export const AdminOrderHistorySection = ({ events }: AdminOrderHistorySectionProps) => (
  <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-brand-900">Historique</h2>
      <p className="mt-1 text-sm text-stone-500">
        Trace des actions et événements enregistrés sur la commande.
      </p>
    </div>
    {events.length === 0 ? (
      <p className="text-sm text-stone-500">Aucun événement enregistré.</p>
    ) : (
      <ul className="space-y-2 text-sm text-stone-700">
        {events.map((event) => (
          <li key={event.id} className="rounded-xl bg-brand-50 px-3 py-2">
            <div className="text-xs text-stone-500">
              {formatOptionalFrenchDateTime(event.createdAt)}
            </div>
            <div>{event.message || event.type}</div>
            {event.actor?.name ? (
              <div className="text-xs text-stone-500">Par {event.actor.name}</div>
            ) : null}
          </li>
        ))}
      </ul>
    )}
  </section>
);
