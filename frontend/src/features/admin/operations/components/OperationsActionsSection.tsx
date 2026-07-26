import { type Dispatch, type SetStateAction } from 'react';
import { type FulfillmentOrderDto } from '@/features/admin/operations/api';
import {
  ActionCard,
  Field,
  operationsUi,
} from '@/features/admin/operations/components/AdminOperationsWidgets';
import {
  type BulkForm,
  type RefundForm,
  type ShippingForms,
  type StockForm,
  type SupportForm,
} from './operationsTypes';
import { OperationsShippingQueue } from './OperationsShippingQueue';
import { OperationsSupportCard } from './OperationsSupportCard';
const { inputClass, primaryActionClass } = operationsUi;

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
  return (
    <section className="mb-8 grid gap-6 xl:grid-cols-2">
      <OperationsShippingQueue fulfillmentOrders={fulfillmentOrders} shippingForms={shippingForms} setShippingForms={setShippingForms} submitShipOrder={submitShipOrder} />

      <OperationsSupportCard supportForm={supportForm} setSupportForm={setSupportForm} submitSupport={submitSupport} />

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
