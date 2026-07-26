import type { Dispatch, SetStateAction } from 'react';

import type { OrderDto } from '@/features/orders/api';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type DeliveryForm = {
  status: string;
  carrier: string;
  trackingNumber: string;
  trackingUrl: string;
  estimatedAt: string;
};

type Props = {
  order: OrderDto;
  deliveryForm: DeliveryForm;
  deliverySaving: boolean;
  setDeliveryForm: Dispatch<SetStateAction<DeliveryForm>>;
  saveDelivery: () => Promise<void>;
};

export const AdminOrderDeliverySection = ({ order, deliveryForm, deliverySaving, setDeliveryForm, saveDelivery }: Props) => (
  <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-brand-900">Livraison</h2>
      <p className="mt-1 text-sm text-stone-500">Informations de suivi visibles aussi côté client.</p>
    </div>
    <div className="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
      <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4 text-sm text-stone-700">
        <div><span className="font-medium text-brand-900">Étape</span> : {order.delivery?.statusLabel ?? 'Préparation en cours'}</div>
        <div><span className="font-medium text-brand-900">Transporteur</span> : {order.delivery?.carrier || '-'}</div>
        <div><span className="font-medium text-brand-900">Numéro de suivi</span> : {order.delivery?.trackingNumber || '-'}</div>
        <div><span className="font-medium text-brand-900">Date estimée</span> : {formatOptionalFrenchDateTime(order.delivery?.estimatedAt)}</div>
        <div><span className="font-medium text-brand-900">Expédiée le</span> : {formatOptionalFrenchDateTime(order.delivery?.shippedAt)}</div>
        <div><span className="font-medium text-brand-900">Livrée le</span> : {formatOptionalFrenchDateTime(order.delivery?.deliveredAt)}</div>
        {order.delivery?.trackingUrl ? <div className="mt-3"><a className="text-brand-700 underline" href={order.delivery.trackingUrl} target="_blank" rel="noreferrer">Ouvrir le lien de suivi</a></div> : null}
      </div>
      <div className="space-y-3 rounded-2xl border border-brand-100 p-4">
        <label className="flex flex-col gap-1 text-sm"><span className="font-medium text-brand-900">Étape</span><select value={deliveryForm.status} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, status: e.target.value }))}><option value="preparing">Préparation en cours</option><option value="shipped">Expédiée</option><option value="in_transit">En transit</option><option value="out_for_delivery">En cours de livraison</option><option value="delivered">Livrée</option><option value="issue">Incident de livraison</option></select></label>
        <label className="flex flex-col gap-1 text-sm"><span className="font-medium text-brand-900">Transporteur</span><input value={deliveryForm.carrier} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, carrier: e.target.value }))} placeholder="Colissimo, DHL..." /></label>
        <label className="flex flex-col gap-1 text-sm"><span className="font-medium text-brand-900">Numéro de suivi</span><input value={deliveryForm.trackingNumber} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, trackingNumber: e.target.value }))} placeholder="Numéro de suivi" /></label>
        <label className="flex flex-col gap-1 text-sm"><span className="font-medium text-brand-900">Lien de suivi</span><input value={deliveryForm.trackingUrl} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, trackingUrl: e.target.value }))} placeholder="https://..." /></label>
        <label className="flex flex-col gap-1 text-sm"><span className="font-medium text-brand-900">Date estimée</span><input type="date" value={deliveryForm.estimatedAt} onChange={(e) => setDeliveryForm((prev) => ({ ...prev, estimatedAt: e.target.value }))} /></label>
        <button type="button" className="register-form__submit" disabled={deliverySaving} onClick={() => void saveDelivery()}>{deliverySaving ? 'Enregistrement...' : 'Enregistrer le suivi'}</button>
      </div>
    </div>
  </section>
);
