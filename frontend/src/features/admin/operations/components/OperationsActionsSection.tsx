import { type Dispatch, type SetStateAction } from 'react';
import { useNavigate } from 'react-router-dom';

import { type FulfillmentOrderDto } from '@/features/admin/operations/api';
import {
  ActionCard,
  Field,
  operationsUi,
} from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatEuroCents } from '@/shared/lib/formatters';
import {
  type BulkForm,
  type RefundForm,
  type ShippingForms,
  type StockForm,
  type SupportForm,
} from './operationsTypes';
const { inputClass, primaryActionClass, secondaryActionClass } = operationsUi;

export const OperationsActionsSection = ({
  bulkForm,
  fulfillmentOrders,
  quoteConversionMessage,
  quoteConversionStatus,
  quoteReference,
  refundForm,
  setBulkForm,
  setQuoteReference,
  setRefundForm,
  setShippingForms,
  setStockForm,
  setSupportForm,
  shippingForms,
  stockForm,
  submitBulk,
  submitQuoteConversion,
  submitRefund,
  submitShipOrder,
  submitStock,
  submitSupport,
  supportForm,
}: {
  bulkForm: BulkForm;
  fulfillmentOrders: FulfillmentOrderDto[];
  quoteConversionMessage: string | null;
  quoteConversionStatus: 'idle' | 'loading' | 'error';
  quoteReference: string;
  refundForm: RefundForm;
  setBulkForm: Dispatch<SetStateAction<BulkForm>>;
  setQuoteReference: (value: string) => void;
  setRefundForm: Dispatch<SetStateAction<RefundForm>>;
  setShippingForms: Dispatch<SetStateAction<ShippingForms>>;
  setStockForm: Dispatch<SetStateAction<StockForm>>;
  setSupportForm: Dispatch<SetStateAction<SupportForm>>;
  shippingForms: ShippingForms;
  stockForm: StockForm;
  submitBulk: () => void;
  submitQuoteConversion: () => void;
  submitRefund: () => void;
  submitShipOrder: (orderId: number) => void;
  submitStock: () => void;
  submitSupport: () => void;
  supportForm: SupportForm;
}) => {
  const navigate = useNavigate();

  return (
    <section className="mb-8 grid gap-6 xl:grid-cols-2">
      <ActionCard
        title="Préparer et expédier"
        description="File des commandes à traiter. Renseigne le suivi puis marque la commande comme expédiée."
      >
        <div className="space-y-3">
          {fulfillmentOrders.length === 0 ? (
            <p className="text-sm text-stone-500">Aucune commande à préparer.</p>
          ) : (
            fulfillmentOrders.map((order) => {
              const form = shippingForms[order.id] ?? {
                carrier: order.delivery.carrier ?? '',
                trackingNumber: order.delivery.trackingNumber ?? '',
                trackingUrl: order.delivery.trackingUrl ?? '',
              };
              return (
                <div key={order.id} className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
                  <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                      <div className="font-semibold text-brand-900">
                        {order.number} · {formatEuroCents(order.totalPriceCents)}
                      </div>
                      <div className="mt-1 text-xs text-stone-500">
                        {order.customer.name} · {order.shipping.postalCode} {order.shipping.city}
                      </div>
                      <div className="mt-2 text-xs text-stone-600">
                        {order.items.map((item) => `${item.quantity}× ${item.name}`).join(' · ')}
                      </div>
                    </div>
                    <button
                      className={secondaryActionClass}
                      type="button"
                      onClick={() => navigate(`/admin/orders/${order.id}`)}
                    >
                      Voir
                    </button>
                  </div>
                  <div className="mt-3 grid gap-2 sm:grid-cols-3">
                    <input
                      className={inputClass}
                      placeholder="Transporteur"
                      value={form.carrier}
                      onChange={(e) =>
                        setShippingForms((p) => ({
                          ...p,
                          [order.id]: { ...form, carrier: e.target.value },
                        }))
                      }
                    />
                    <input
                      className={inputClass}
                      placeholder="Numéro de suivi"
                      value={form.trackingNumber}
                      onChange={(e) =>
                        setShippingForms((p) => ({
                          ...p,
                          [order.id]: { ...form, trackingNumber: e.target.value },
                        }))
                      }
                    />
                    <input
                      className={inputClass}
                      placeholder="Lien de suivi"
                      value={form.trackingUrl}
                      onChange={(e) =>
                        setShippingForms((p) => ({
                          ...p,
                          [order.id]: { ...form, trackingUrl: e.target.value },
                        }))
                      }
                    />
                  </div>
                  <button
                    className={`${primaryActionClass} mt-3`}
                    type="button"
                    onClick={() => submitShipOrder(order.id)}
                  >
                    Marquer expédiée
                  </button>
                </div>
              );
            })
          )}
        </div>
      </ActionCard>

      <ActionCard
        title="Créer un dossier SAV"
        description="À utiliser quand un client signale un problème ou quand une commande nécessite un suivi manuel."
      >
        <div className="grid gap-3 sm:grid-cols-2">
          <Field label="ID client" helper="Obligatoire. À récupérer depuis la fiche client.">
            <input
              className={inputClass}
              inputMode="numeric"
              placeholder="Ex. 42"
              value={supportForm.customerId}
              onChange={(e) => setSupportForm((p) => ({ ...p, customerId: e.target.value }))}
            />
          </Field>
          <Field
            label="ID commande"
            helper="Optionnel si la demande n’est pas liée à une commande."
          >
            <input
              className={inputClass}
              inputMode="numeric"
              placeholder="Ex. 128"
              value={supportForm.orderId}
              onChange={(e) => setSupportForm((p) => ({ ...p, orderId: e.target.value }))}
            />
          </Field>
          <Field label="Sujet" className="sm:col-span-2">
            <input
              className={inputClass}
              placeholder="Ex. Produit reçu endommagé"
              value={supportForm.subject}
              onChange={(e) => setSupportForm((p) => ({ ...p, subject: e.target.value }))}
            />
          </Field>
          <Field label="Type de demande" className="sm:col-span-2">
            <select
              className={inputClass}
              value={supportForm.reason}
              onChange={(e) => setSupportForm((p) => ({ ...p, reason: e.target.value }))}
            >
              <option value="defective_product">Produit défectueux</option>
              <option value="wrong_order">Erreur commande</option>
              <option value="return">Retour</option>
              <option value="exchange">Échange</option>
              <option value="refund">Remboursement</option>
              <option value="other">Autre</option>
            </select>
          </Field>
          <Field label="Message / contexte" className="sm:col-span-2">
            <textarea
              className={inputClass}
              rows={3}
              placeholder="Résumé clair du problème client"
              value={supportForm.message}
              onChange={(e) => setSupportForm((p) => ({ ...p, message: e.target.value }))}
            />
          </Field>
          <Field
            label="Notes internes"
            className="sm:col-span-2"
            helper="Visible uniquement côté admin."
          >
            <textarea
              className={inputClass}
              rows={3}
              placeholder="Décision, historique, prochaine action..."
              value={supportForm.internalNotes}
              onChange={(e) => setSupportForm((p) => ({ ...p, internalNotes: e.target.value }))}
            />
          </Field>
        </div>
        <button
          className={primaryActionClass}
          type="button"
          onClick={submitSupport}
          disabled={!supportForm.customerId}
        >
          Créer le dossier SAV
        </button>
      </ActionCard>

      <ActionCard
        title="Créer un suivi de remboursement"
        description="Ce suivi sert à piloter la décision. Il ne déclenche pas automatiquement un remboursement Stripe."
        warning="Action comptable sensible : vérifie la commande et le montant avant de marquer le suivi comme traité."
      >
        <div className="grid gap-3 sm:grid-cols-2">
          <Field label="ID commande">
            <input
              className={inputClass}
              inputMode="numeric"
              placeholder="Ex. 128"
              value={refundForm.orderId}
              onChange={(e) => setRefundForm((p) => ({ ...p, orderId: e.target.value }))}
            />
          </Field>
          <Field label="Montant" helper="En centimes. Exemple : 1990 = 19,90 €.">
            <input
              className={inputClass}
              inputMode="numeric"
              placeholder="Ex. 1990"
              value={refundForm.amountCents}
              onChange={(e) => setRefundForm((p) => ({ ...p, amountCents: e.target.value }))}
            />
          </Field>
          <Field label="Motif" className="sm:col-span-2">
            <input
              className={inputClass}
              placeholder="Ex. Retour client accepté"
              value={refundForm.reason}
              onChange={(e) => setRefundForm((p) => ({ ...p, reason: e.target.value }))}
            />
          </Field>
          <Field label="Notes internes" className="sm:col-span-2">
            <textarea
              className={inputClass}
              rows={3}
              placeholder="Décision, preuve, référence Stripe si déjà traitée..."
              value={refundForm.internalNotes}
              onChange={(e) => setRefundForm((p) => ({ ...p, internalNotes: e.target.value }))}
            />
          </Field>
        </div>
        <button
          className={primaryActionClass}
          type="button"
          onClick={submitRefund}
          disabled={!refundForm.orderId || !refundForm.amountCents}
        >
          Créer le suivi remboursement
        </button>
      </ActionCard>

      <ActionCard
        title="Corriger un stock"
        description="Ajoute ou retire une quantité avec une trace exploitable dans l’historique."
      >
        <div className="grid gap-3 sm:grid-cols-2">
          <Field label="ID produit">
            <input
              className={inputClass}
              inputMode="numeric"
              placeholder="Ex. 15"
              value={stockForm.productId}
              onChange={(e) => setStockForm((p) => ({ ...p, productId: e.target.value }))}
            />
          </Field>
          <Field label="Quantité à appliquer" helper="+5 ajoute du stock, -2 retire du stock.">
            <input
              className={inputClass}
              placeholder="Ex. +5 ou -2"
              value={stockForm.delta}
              onChange={(e) => setStockForm((p) => ({ ...p, delta: e.target.value }))}
            />
          </Field>
          <Field label="Motif" className="sm:col-span-2">
            <select
              className={inputClass}
              value={stockForm.reason}
              onChange={(e) => setStockForm((p) => ({ ...p, reason: e.target.value }))}
            >
              <option value="adjustment">Correction</option>
              <option value="restock">Réapprovisionnement</option>
              <option value="return">Retour</option>
              <option value="damage">Casse</option>
              <option value="reservation">Réservation</option>
            </select>
          </Field>
          <Field label="Note" className="sm:col-span-2">
            <textarea
              className={inputClass}
              rows={3}
              placeholder="Pourquoi ce stock change ?"
              value={stockForm.note}
              onChange={(e) => setStockForm((p) => ({ ...p, note: e.target.value }))}
            />
          </Field>
        </div>
        <button
          className={primaryActionClass}
          type="button"
          onClick={submitStock}
          disabled={!stockForm.productId || !stockForm.delta}
        >
          Enregistrer le mouvement
        </button>
      </ActionCard>

      <ActionCard
        title="Actions rapides"
        description="Regroupe les actions ponctuelles qui modifient plusieurs données ou transforment un devis."
      >
        <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
          <h3 className="font-semibold text-brand-900">Changer le statut de commandes</h3>
          <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_180px]">
            <Field
              label="IDs commandes"
              helper="Sépare les IDs par des virgules. Exemple : 101, 102, 103."
            >
              <input
                className={inputClass}
                placeholder="101, 102, 103"
                value={bulkForm.orderIds}
                onChange={(e) => setBulkForm((p) => ({ ...p, orderIds: e.target.value }))}
              />
            </Field>
            <Field label="Nouveau statut">
              <select
                className={inputClass}
                value={bulkForm.status}
                onChange={(e) => setBulkForm((p) => ({ ...p, status: e.target.value }))}
              >
                <option value="confirmed">Confirmée</option>
                <option value="delivered">Livrée</option>
                <option value="cancelled">Annulée</option>
                <option value="pending">En attente</option>
              </select>
            </Field>
          </div>
          <button
            className={`${primaryActionClass} mt-3`}
            type="button"
            onClick={submitBulk}
            disabled={!bulkForm.orderIds}
          >
            Appliquer le statut
          </button>
        </div>
        <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
          <h3 className="font-semibold text-brand-900">Convertir un devis en commande</h3>
          <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
            <Field
              label="ID ou numéro du devis"
              helper="Tu peux saisir l’ID interne ou le numéro visible dans la liste des devis."
            >
              <input
                className={inputClass}
                placeholder="Ex. 77 ou DEV-2026-001"
                value={quoteReference}
                onChange={(e) => setQuoteReference(e.target.value)}
              />
            </Field>
            <button
              className={primaryActionClass}
              type="button"
              onClick={submitQuoteConversion}
              disabled={!quoteReference.trim() || quoteConversionStatus === 'loading'}
            >
              {quoteConversionStatus === 'loading' ? 'Conversion...' : 'Convertir'}
            </button>
          </div>
          {quoteConversionMessage && (
            <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
              {quoteConversionMessage}
            </p>
          )}
        </div>
      </ActionCard>
    </section>
  );
};
