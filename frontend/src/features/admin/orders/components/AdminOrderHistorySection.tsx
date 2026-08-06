import { useMemo, useState } from 'react';

import type { OrderEventDto } from '@/features/orders/publicApi';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { clampAtLeast, clampWithin } from '@/shared/lib/number';

type AdminOrderHistorySectionProps = {
  events: OrderEventDto[];
};

const EVENTS_PER_PAGE = 10;

export const AdminOrderHistorySection = ({ events }: AdminOrderHistorySectionProps) => {
  const [page, setPage] = useState(1);
  const totalPages = clampAtLeast(Math.ceil(events.length / EVENTS_PER_PAGE), 1);
  const currentPage = clampWithin(page, 1, totalPages);
  const visibleEvents = useMemo(() => {
    const start = (currentPage - 1) * EVENTS_PER_PAGE;

    return events.slice(start, start + EVENTS_PER_PAGE);
  }, [currentPage, events]);

  return (
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
        <>
          <ul className="space-y-2 text-sm text-stone-700">
            {visibleEvents.map((event) => (
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
          <PaginationControls
            page={currentPage}
            total={events.length}
            totalLabel="événement"
            totalPages={totalPages}
            onPageChange={setPage}
          />
        </>
      )}
    </section>
  );
};
