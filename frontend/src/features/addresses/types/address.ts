import type { AddressDto } from '@/features/addresses/api/addressesApi';

export type AddressFormState = {
  type: 'personal' | 'professional';
  name: string;
  address: string;
  addressComplement: string;
  postalCode: string;
  city: string;
  company: string;
  companySiren: string;
  companyVatNumber: string;
  isDefault?: boolean;
};

export const emptyAddressForm = (): AddressFormState => ({
  type: 'personal',
  name: '',
  address: '',
  addressComplement: '',
  postalCode: '',
  city: '',
  company: '',
  companySiren: '',
  companyVatNumber: '',
  isDefault: false,
});

export const addressToForm = (address: AddressDto): AddressFormState => ({
  type: address.type,
  name: address.name,
  address: address.address,
  addressComplement: address.addressComplement ?? '',
  postalCode: address.postalCode,
  city: address.city,
  company: address.company ?? '',
  companySiren: address.companySiren ?? '',
  companyVatNumber: address.companyVatNumber ?? '',
});

export const addressFormToPayload = (form: AddressFormState) => ({
  type: form.type,
  name: form.name,
  address: form.address,
  addressComplement: form.addressComplement.trim() || null,
  postalCode: form.postalCode,
  city: form.city,
  company: form.type === 'professional' ? form.company.trim() || null : null,
  companySiren: form.type === 'professional' ? form.companySiren.trim() || null : null,
  companyVatNumber: form.type === 'professional' ? form.companyVatNumber.trim() || null : null,
  ...(form.isDefault !== undefined ? { isDefault: form.isDefault } : {}),
});
