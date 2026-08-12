import type { Dispatch, SetStateAction } from 'react';

import { ActionCard, Field, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import type { SupportForm } from './operationsTypes';
import { OperationsCustomerOrderPicker } from './OperationsCustomerOrderPicker';

const { inputClass, primaryActionClass } = operationsUi;

export const OperationsSupportCard = ({ supportForm, setSupportForm, submitSupport, withHeading = true }: {
  supportForm: SupportForm;
  setSupportForm: Dispatch<SetStateAction<SupportForm>>;
  submitSupport: () => void;
  withHeading?: boolean;
}) => (
  <ActionCard {...(withHeading ? {
    title: 'Créer un dossier SAV',
    description: 'À utiliser quand un client signale un problème ou quand une commande nécessite un suivi manuel.',
  } : {})}>
    <div className="grid gap-3 sm:grid-cols-2">
      <OperationsCustomerOrderPicker
        customerId={supportForm.customerId}
        orderId={supportForm.orderId}
        onCustomerChange={(customerId) =>
          setSupportForm((current) => ({ ...current, customerId, orderId: '' }))
        }
        onOrderChange={(orderId) =>
          setSupportForm((current) => ({ ...current, orderId }))
        }
        orderLabel="Commande"
        orderHelper="Optionnel si la demande n’est pas liée à une commande."
      />
      <Field label="Sujet" className="sm:col-span-2"><input className={inputClass} placeholder="Ex. Produit reçu endommagé" value={supportForm.subject} onChange={(event) => setSupportForm((current) => ({ ...current, subject: event.target.value }))} /></Field>
      <Field label="Type de demande" className="sm:col-span-2"><select className={inputClass} value={supportForm.reason} onChange={(event) => setSupportForm((current) => ({ ...current, reason: event.target.value }))}><option value="defective_product">Produit défectueux</option><option value="wrong_order">Erreur commande</option><option value="return">Retour</option><option value="exchange">Échange</option><option value="refund">Remboursement</option><option value="other">Autre</option></select></Field>
      <Field label="Message / contexte" className="sm:col-span-2"><textarea className={inputClass} rows={3} placeholder="Résumé clair du problème client" value={supportForm.message} onChange={(event) => setSupportForm((current) => ({ ...current, message: event.target.value }))} /></Field>
      <Field label="Notes internes" className="sm:col-span-2" helper="Visible uniquement côté admin."><textarea className={inputClass} rows={3} placeholder="Décision, historique, prochaine action..." value={supportForm.internalNotes} onChange={(event) => setSupportForm((current) => ({ ...current, internalNotes: event.target.value }))} /></Field>
    </div>
    <button className={primaryActionClass} type="button" onClick={submitSupport} disabled={!supportForm.customerId}>Créer le dossier SAV</button>
  </ActionCard>
);
