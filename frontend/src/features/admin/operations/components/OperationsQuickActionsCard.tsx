import type { Dispatch, SetStateAction } from 'react';

import { ActionCard, Field, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import type { BulkForm } from './operationsTypes';

const { inputClass, primaryActionClass } = operationsUi;

export const OperationsQuickActionsCard = ({ bulkForm, quoteConversionMessage, quoteConversionStatus, quoteReference, setBulkForm, setQuoteReference, submitBulk, submitQuoteConversion }: {
  bulkForm: BulkForm;
  quoteConversionMessage: string | null;
  quoteConversionStatus: 'idle' | 'loading' | 'error';
  quoteReference: string;
  setBulkForm: Dispatch<SetStateAction<BulkForm>>;
  setQuoteReference: (value: string) => void;
  submitBulk: () => void;
  submitQuoteConversion: () => void;
}) => (
  <ActionCard title="Actions rapides" description="Regroupe les actions ponctuelles qui modifient plusieurs données ou transforment un devis.">
    <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
      <h3 className="font-semibold text-brand-900">Changer le statut de commandes</h3>
      <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_180px]">
        <Field label="IDs commandes" helper="Sépare les IDs par des virgules. Exemple : 101, 102, 103."><input className={inputClass} placeholder="101, 102, 103" value={bulkForm.orderIds} onChange={(event) => setBulkForm((current) => ({ ...current, orderIds: event.target.value }))} /></Field>
        <Field label="Nouveau statut"><select className={inputClass} value={bulkForm.status} onChange={(event) => setBulkForm((current) => ({ ...current, status: event.target.value }))}><option value="confirmed">Confirmée</option><option value="delivered">Livrée</option><option value="cancelled">Annulée</option><option value="pending">En attente</option></select></Field>
      </div>
      <button className={`${primaryActionClass} mt-3`} type="button" onClick={submitBulk} disabled={!bulkForm.orderIds}>Appliquer le statut</button>
    </div>
    <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
      <h3 className="font-semibold text-brand-900">Convertir un devis en commande</h3>
      <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
        <Field label="ID ou numéro du devis" helper="Tu peux saisir l’ID interne ou le numéro visible dans la liste des devis."><input className={inputClass} placeholder="Ex. 77 ou DEV-2026-001" value={quoteReference} onChange={(event) => setQuoteReference(event.target.value)} /></Field>
        <button className={primaryActionClass} type="button" onClick={submitQuoteConversion} disabled={!quoteReference.trim() || quoteConversionStatus === 'loading'}>{quoteConversionStatus === 'loading' ? 'Conversion...' : 'Convertir'}</button>
      </div>
      {quoteConversionMessage && <p className="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{quoteConversionMessage}</p>}
    </div>
  </ActionCard>
);
