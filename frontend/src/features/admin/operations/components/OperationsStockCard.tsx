import type { Dispatch, SetStateAction } from 'react';

import { ActionCard, Field, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import type { StockForm } from './operationsTypes';

const { inputClass, primaryActionClass } = operationsUi;

export const OperationsStockCard = ({ stockForm, setStockForm, submitStock }: {
  stockForm: StockForm;
  setStockForm: Dispatch<SetStateAction<StockForm>>;
  submitStock: () => void;
}) => (
  <ActionCard title="Corriger un stock" description="Ajoute ou retire une quantité avec une trace exploitable dans l’historique.">
    <div className="grid gap-3 sm:grid-cols-2">
      <Field label="ID produit"><input className={inputClass} inputMode="numeric" placeholder="Ex. 15" value={stockForm.productId} onChange={(event) => setStockForm((current) => ({ ...current, productId: event.target.value }))} /></Field>
      <Field label="Quantité à appliquer" helper="+5 ajoute du stock, -2 retire du stock."><input className={inputClass} placeholder="Ex. +5 ou -2" value={stockForm.delta} onChange={(event) => setStockForm((current) => ({ ...current, delta: event.target.value }))} /></Field>
      <Field label="Motif" className="sm:col-span-2"><select className={inputClass} value={stockForm.reason} onChange={(event) => setStockForm((current) => ({ ...current, reason: event.target.value }))}><option value="adjustment">Correction</option><option value="restock">Réapprovisionnement</option><option value="return">Retour</option><option value="damage">Casse</option><option value="reservation">Réservation</option></select></Field>
      <Field label="Note" className="sm:col-span-2"><textarea className={inputClass} rows={3} placeholder="Pourquoi ce stock change ?" value={stockForm.note} onChange={(event) => setStockForm((current) => ({ ...current, note: event.target.value }))} /></Field>
    </div>
    <button className={primaryActionClass} type="button" onClick={submitStock} disabled={!stockForm.productId || !stockForm.delta}>Enregistrer le mouvement</button>
  </ActionCard>
);
