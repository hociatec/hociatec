import { useCallback, useEffect, useState } from 'react';

import {
  createAddress,
  deleteAddress,
  fetchMyAddresses,
  setDefaultAddress,
  updateAddress,
  type AddressDto,
} from '@/features/addresses/api/addressesApi';
import {
  addressFormToPayload,
  addressToForm,
  emptyAddressForm,
  type AddressFormState,
} from '@/features/addresses/types/address';
import { useToast } from '@/shared/components/ui/toast';

export const useAddressesPage = () => {
  const { show } = useToast();
  const [items, setItems] = useState<AddressDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<number | 'new' | null>(null);
  const [form, setForm] = useState<AddressFormState>(emptyAddressForm);
  const [editing, setEditing] = useState<AddressDto | null>(null);
  const [editForm, setEditForm] = useState<AddressFormState>(emptyAddressForm);

  useEffect(() => {
    void fetchMyAddresses()
      .then(setItems)
      .catch((error: unknown) =>
        show(error instanceof Error ? error.message : 'Impossible de charger les adresses.', {
          variant: 'error',
        }),
      )
      .finally(() => setLoading(false));
  }, [show]);

  const handleCreate = useCallback(() => {
    if (!form.name || !form.address || !form.postalCode || !form.city) return;
    setSavingId('new');
    void createAddress(addressFormToPayload(form))
      .then((address) => {
        setItems((current) =>
          [...current, address].sort(
            (left, right) => Number(right.isDefault) - Number(left.isDefault),
          ),
        );
        setForm(emptyAddressForm());
        show('Adresse ajoutée', { variant: 'success' });
      })
      .catch((error: unknown) =>
        show(error instanceof Error ? error.message : 'Impossible de créer l’adresse.', {
          variant: 'error',
        }),
      )
      .finally(() => setSavingId(null));
  }, [form, show]);

  const openEdit = (address: AddressDto) => {
    setEditing(address);
    setEditForm(addressToForm(address));
  };
  const handleUpdate = useCallback(() => {
    if (!editing) return;
    setSavingId(editing.id);
    void updateAddress(editing.id, addressFormToPayload(editForm))
      .then((address) => {
        setItems((current) => current.map((item) => (item.id === address.id ? address : item)));
        setEditing(null);
        show('Adresse mise à jour', { variant: 'success' });
      })
      .catch((error: unknown) =>
        show(error instanceof Error ? error.message : 'Impossible de modifier l’adresse.', {
          variant: 'error',
        }),
      )
      .finally(() => setSavingId(null));
  }, [editForm, editing, show]);

  const handleDelete = (id: number) => {
    setSavingId(id);
    void deleteAddress(id)
      .then(() => {
        setItems((current) => current.filter((item) => item.id !== id));
        show('Adresse supprimée', { variant: 'success' });
      })
      .catch((error: unknown) =>
        show(error instanceof Error ? error.message : 'Impossible de supprimer l’adresse.', {
          variant: 'error',
        }),
      )
      .finally(() => setSavingId(null));
  };

  const handleSetDefault = (id: number) => {
    void setDefaultAddress(id)
      .then(() => {
        setItems((current) => current.map((item) => ({ ...item, isDefault: item.id === id })));
        show('Adresse par défaut définie', { variant: 'success' });
      })
      .catch((error: unknown) =>
        show(
          error instanceof Error ? error.message : 'Impossible de définir l’adresse par défaut.',
          { variant: 'error' },
        ),
      );
  };

  return {
    editForm,
    editing,
    form,
    handleCreate,
    handleDelete,
    handleSetDefault,
    handleUpdate,
    items,
    loading,
    openEdit,
    savingId,
    setEditForm,
    setEditing,
    setForm,
  };
};
