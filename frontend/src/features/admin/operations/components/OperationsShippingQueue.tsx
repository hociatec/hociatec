import { useNavigate } from 'react-router';

import type { FulfillmentOrderDto } from '@/features/admin/operations/api';
import { ActionCard, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatEuroCents } from '@/shared/lib/formatters';
import type { Dispatch, SetStateAction } from 'react';
import type { ShippingForms } from './operationsTypes';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginationMeta } from '@/shared/types/api';

const { inputClass, primaryActionClass, secondaryActionClass } = operationsUi;

export const OperationsShippingQueue = ({ fulfillmentMeta, fulfillmentOrders, setFulfillmentPage, shippingForms, setShippingForms, submitShipOrder }: {
  fulfillmentMeta: PaginationMeta;
  fulfillmentOrders: FulfillmentOrderDto[];
  setFulfillmentPage: Dispatch<SetStateAction<number>>;
  shippingForms: ShippingForms;
  setShippingForms: Dispatch<SetStateAction<ShippingForms>>;
  submitShipOrder: (orderId: number) => void;
}) => {
  const navigate = useNavigate();

  return (
    <ActionCard title="Préparer et expédier" description="File des commandes à traiter. Renseigne le suivi puis marque la commande comme expédiée.">
      <div className="space-y-3">
        {fulfillmentOrders.length === 0 ? <p className="text-sm text-stone-500">Aucune commande à préparer.</p> : fulfillmentOrders.map((order) => {
          const form = shippingForms[order.id] ?? { carrier: order.delivery.carrier ?? '', trackingNumber: order.delivery.trackingNumber ?? '', trackingUrl: order.delivery.trackingUrl ?? '' };
          const update = (field: keyof typeof form, value: string) => setShippingForms((current) => ({ ...current, [order.id]: { ...form, [field]: value } }));
          return (
            <div key={order.id} className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
              <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div><div className="font-semibold text-brand-900">{order.number} · {formatEuroCents(order.totalPriceCents)}</div><div className="mt-1 text-xs text-stone-500">{order.customer.name} · {order.shipping.postalCode} {order.shipping.city}</div><div className="mt-2 text-xs text-stone-600">{order.items.map((item) => `${item.quantity}× ${item.name}`).join(' · ')}</div></div>
                <button className={secondaryActionClass} type="button" onClick={() => navigate(`/admin/orders/${order.id}`)}>Voir</button>
              </div>
              <div className="mt-3 grid gap-2 sm:grid-cols-3">
                <input className={inputClass} placeholder="Transporteur" value={form.carrier} onChange={(event) => update('carrier', event.target.value)} />
                <input className={inputClass} placeholder="Numéro de suivi" value={form.trackingNumber} onChange={(event) => update('trackingNumber', event.target.value)} />
                <input className={inputClass} placeholder="Lien de suivi" value={form.trackingUrl} onChange={(event) => update('trackingUrl', event.target.value)} />
              </div>
              <button className={`${primaryActionClass} mt-3`} type="button" onClick={() => submitShipOrder(order.id)}>Marquer expédiée</button>
            </div>
          );
        })}
      </div>
      <PaginationControls
        page={fulfillmentMeta.page}
        total={fulfillmentMeta.total}
        totalLabel="commande"
        totalPages={fulfillmentMeta.totalPages}
        onPageChange={setFulfillmentPage}
      />
    </ActionCard>
  );
};
