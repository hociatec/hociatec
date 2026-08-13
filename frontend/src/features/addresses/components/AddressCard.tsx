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
          <span className="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">
            {address.type === 'professional' ? 'Professionnelle' : 'Personnelle'}
          </span>
          {address.isDefault && (
            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
              Par défaut
            </span>
          )}
        </div>
        {address.type === 'professional' && address.company ? (
          <p className="text-sm text-stone-700">{address.company}</p>
        ) : null}
        <p className="text-sm text-stone-700">{address.address}</p>
        {address.addressComplement ? (
          <p className="text-sm text-stone-700">{address.addressComplement}</p>
        ) : null}
        <p className="text-sm text-stone-600">
          {address.postalCode} {address.city}
        </p>
        {address.type === 'professional' && address.companySiren ? (
          <p className="text-sm text-stone-600">SIREN : {address.companySiren}</p>
        ) : null}
        {address.type === 'professional' && address.companyVatNumber ? (
          <p className="text-sm text-stone-600">TVA : {address.companyVatNumber}</p>
        ) : null}
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
