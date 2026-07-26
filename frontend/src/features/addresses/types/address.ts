import type { AddressDto } from '@/features/addresses/api/addressesApi';

export type AddressFormState = {
  name: string;
  address: string;
  postalCode: string;
  city: string;
  company: string;
  companySiren: string;
  companyVatNumber: string;
  purchaseOrderNumber: string;
  isDefault?: boolean;
};

export const emptyAddressForm = (): AddressFormState => ({ name: '', address: '', postalCode: '', city: '', company: '', companySiren: '', companyVatNumber: '', purchaseOrderNumber: '', isDefault: false });

export const addressToForm = (address: AddressDto): AddressFormState => ({
  name: address.name,
  address: address.address,
  postalCode: address.postalCode,
  city: address.city,
  company: address.company ?? '',
  companySiren: address.companySiren ?? '',
  companyVatNumber: address.companyVatNumber ?? '',
  purchaseOrderNumber: address.purchaseOrderNumber ?? '',
});

export const addressFormToPayload = (form: AddressFormState) => ({
  name: form.name,
  address: form.address,
  postalCode: form.postalCode,
  city: form.city,
  company: form.company.trim() || undefined,
  companySiren: form.companySiren.trim() || undefined,
  companyVatNumber: form.companyVatNumber.trim() || undefined,
  purchaseOrderNumber: form.purchaseOrderNumber.trim() || undefined,
  isDefault: form.isDefault,
});
