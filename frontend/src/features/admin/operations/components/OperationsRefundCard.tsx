import type { Dispatch, SetStateAction } from 'react';

import { ActionCard, Field, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import type { RefundForm } from './operationsTypes';

const { inputClass, primaryActionClass } = operationsUi;

export const OperationsRefundCard = ({ refundForm, setRefundForm, submitRefund }: {
  refundForm: RefundForm;
  setRefundForm: Dispatch<SetStateAction<RefundForm>>;
  submitRefund: () => void;
}) => (
  <ActionCard title="Créer un suivi de remboursement" description="Ce suivi sert à piloter la décision. Il ne déclenche pas automatiquement un remboursement Stripe." warning="Action comptable sensible : vérifie la commande et le montant avant de marquer le suivi comme traité.">
    <div className="grid gap-3 sm:grid-cols-2">
      <Field label="ID commande"><input className={inputClass} inputMode="numeric" placeholder="Ex. 128" value={refundForm.orderId} onChange={(event) => setRefundForm((current) => ({ ...current, orderId: event.target.value }))} /></Field>
      <Field label="Montant" helper="En centimes. Exemple : 1990 = 19,90 €."><input className={inputClass} inputMode="numeric" placeholder="Ex. 1990" value={refundForm.amountCents} onChange={(event) => setRefundForm((current) => ({ ...current, amountCents: event.target.value }))} /></Field>
      <Field label="Motif" className="sm:col-span-2"><input className={inputClass} placeholder="Ex. Retour client accepté" value={refundForm.reason} onChange={(event) => setRefundForm((current) => ({ ...current, reason: event.target.value }))} /></Field>
      <Field label="Notes internes" className="sm:col-span-2"><textarea className={inputClass} rows={3} placeholder="Décision, preuve, référence Stripe si déjà traitée..." value={refundForm.internalNotes} onChange={(event) => setRefundForm((current) => ({ ...current, internalNotes: event.target.value }))} /></Field>
    </div>
    <button className={primaryActionClass} type="button" onClick={submitRefund} disabled={!refundForm.orderId || !refundForm.amountCents}>Créer le suivi remboursement</button>
  </ActionCard>
);
