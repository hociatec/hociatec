import type { Dispatch, SetStateAction } from 'react';

import type { AddressFormState } from '@/features/addresses/types/address';

const inputClass =
  'rounded-xl border border-brand-100 px-4 py-3 outline-none transition focus:border-brand-300';
const labelClass = 'grid gap-2 text-sm font-medium text-stone-700';

export const AddressFields = ({
  form,
  setForm,
}: {
  form: AddressFormState;
  setForm: Dispatch<SetStateAction<AddressFormState>>;
}) => (
  <>
    <label className={labelClass}>
      Nom
      <input
        className={inputClass}
        placeholder="Prénom Nom ou Libellé du lieu"
        value={form.name}
        onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
      />
    </label>
    <label className={labelClass}>
      Adresse
      <input
        className={inputClass}
        placeholder="Numéro et rue"
        value={form.address}
        onChange={(event) => setForm((current) => ({ ...current, address: event.target.value }))}
      />
    </label>
    <div className="grid gap-4 md:grid-cols-2">
      <label className={labelClass}>
        Code postal
        <input
          className={inputClass}
          placeholder="92000"
          value={form.postalCode}
          onChange={(event) =>
            setForm((current) => ({ ...current, postalCode: event.target.value }))
          }
        />
      </label>
      <label className={labelClass}>
        Ville
        <input
          className={inputClass}
          placeholder="Nanterre"
          value={form.city}
          onChange={(event) => setForm((current) => ({ ...current, city: event.target.value }))}
        />
      </label>
    </div>
    <AddressBusinessFields form={form} setForm={setForm} />
  </>
);

const AddressBusinessFields = ({
  form,
  setForm,
}: {
  form: AddressFormState;
  setForm: Dispatch<SetStateAction<AddressFormState>>;
}) => (
  <>
    <div className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
      <p className="text-sm font-semibold text-brand-900">
        Informations de facturation professionnelle
      </p>
      <p className="mt-1 text-sm text-stone-600">
        Optionnel. À renseigner si la facture doit comporter des mentions société.
      </p>
    </div>
    <label className={labelClass}>
      Société
      <input
        className={inputClass}
        placeholder="Nom de la société"
        value={form.company}
        onChange={(event) => setForm((current) => ({ ...current, company: event.target.value }))}
      />
    </label>
    <div className="grid gap-4 md:grid-cols-2">
      <label className={labelClass}>
        SIREN client
        <input
          className={inputClass}
          placeholder="123456789"
          value={form.companySiren}
          onChange={(event) =>
            setForm((current) => ({ ...current, companySiren: event.target.value }))
          }
        />
      </label>
      <label className={labelClass}>
        TVA intracommunautaire
        <input
          className={inputClass}
          placeholder="FR12345678901"
          value={form.companyVatNumber}
          onChange={(event) =>
            setForm((current) => ({ ...current, companyVatNumber: event.target.value }))
          }
        />
      </label>
    </div>
    <label className={labelClass}>
      Bon de commande
      <input
        className={inputClass}
        placeholder="BC-2026-001"
        value={form.purchaseOrderNumber}
        onChange={(event) =>
          setForm((current) => ({ ...current, purchaseOrderNumber: event.target.value }))
        }
      />
    </label>
  </>
);
