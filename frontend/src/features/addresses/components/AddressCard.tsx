import type { AddressDto } from '@/features/addresses/api/addressesApi';

export const AddressCard = ({
  address,
  deleting,
  onDelete,
  onEdit,
  onSetDefault,
}: {
  address: AddressDto;
  deleting: boolean;
  onDelete: () => void;
  onEdit: () => void;
  onSetDefault: () => void;
}) => (
  <li className="rounded-2xl border border-brand-100 p-4">
    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div className="space-y-1">
        <div className="flex flex-wrap items-center gap-2">
          <h3 className="text-base font-semibold text-brand-900">{address.name}</h3>
          {address.isDefault && (
            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
              Par défaut
            </span>
          )}
        </div>
        {address.company && <p className="text-sm font-medium text-stone-800">{address.company}</p>}
        <p className="text-sm text-stone-700">{address.address}</p>
        <p className="text-sm text-stone-600">
          {address.postalCode} {address.city}
        </p>
        {address.companySiren && (
          <p className="text-xs text-stone-500">SIREN : {address.companySiren}</p>
        )}
        {address.companyVatNumber && (
          <p className="text-xs text-stone-500">TVA : {address.companyVatNumber}</p>
        )}
        {address.purchaseOrderNumber && (
          <p className="text-xs text-stone-500">Bon de commande : {address.purchaseOrderNumber}</p>
        )}
      </div>
      <div className="flex flex-wrap gap-2 lg:justify-end">
        <button type="button" className="address-button" onClick={onEdit}>
          Modifier
        </button>
        <button
          type="button"
          className="address-button"
          onClick={onSetDefault}
          disabled={address.isDefault}
        >
          Définir par défaut
        </button>
        <button
          type="button"
          className="address-button address-button--danger"
          onClick={onDelete}
          disabled={deleting}
        >
          Supprimer
        </button>
      </div>
    </div>
  </li>
);
