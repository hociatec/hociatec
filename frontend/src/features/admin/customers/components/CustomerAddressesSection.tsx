import { type AdminCustomerAddressDto } from '@/features/admin/customers/api';
import { formatAddress } from './customerDetailShared';

export const CustomerAddressesSection = ({ addresses }: { addresses: AdminCustomerAddressDto[] }) => (
  <section className="rounded-2xl border border-brand-100 p-4">
    <h2 className="mb-3 font-semibold">Adresses</h2>
    {addresses.length === 0 ? (
      <p className="text-sm text-stone-500">Aucune adresse enregistrée.</p>
    ) : (
      <div className="grid gap-4 md:grid-cols-2">
        {addresses.map((address) => (
          <div key={address.id} className="rounded-2xl bg-brand-50 p-4 text-sm text-stone-700">
            <div className="font-semibold text-brand-900">{address.name} {address.isDefault ? '· Par défaut' : ''}</div>
            <div>{formatAddress(address)}</div>
            {address.company ? <div>Société: {address.company}</div> : null}
            {address.companySiren ? <div>SIREN: {address.companySiren}</div> : null}
            {address.companyVatNumber ? <div>TVA: {address.companyVatNumber}</div> : null}
            {address.purchaseOrderNumber ? <div>BC: {address.purchaseOrderNumber}</div> : null}
          </div>
        ))}
      </div>
    )}
  </section>
);
