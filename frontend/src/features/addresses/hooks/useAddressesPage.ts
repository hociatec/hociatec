import { useCallback, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

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
import { addressQueryKeys } from '@/shared/lib/queryKeys';

export const useAddressesPage = () => {
  const { show } = useToast();
  const queryClient = useQueryClient();
  const [savingId, setSavingId] = useState<number | 'new' | null>(null);
  const [creating, setCreating] = useState(false);
  const [form, setForm] = useState<AddressFormState>(emptyAddressForm);
  const [editing, setEditing] = useState<AddressDto | null>(null);
  const [editForm, setEditForm] = useState<AddressFormState>(emptyAddressForm);
  const addressesQuery = useQuery<AddressDto[], Error>({
    queryKey: addressQueryKeys.mine(),
    queryFn: fetchMyAddresses,
  });
  const invalidateAddresses = () => queryClient.invalidateQueries({ queryKey: addressQueryKeys.mine() });
  const createMutation = useMutation({
    mutationFn: createAddress,
    onSuccess: () => {
      void invalidateAddresses();
      setForm(emptyAddressForm());
      setCreating(false);
      show('Adresse ajoutée', { variant: 'success' });
    },
    onError: (error) =>
      show(error instanceof Error ? error.message : 'Impossible de créer l’adresse.', {
        variant: 'error',
      }),
    onSettled: () => setSavingId(null),
  });
  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: ReturnType<typeof addressFormToPayload> }) =>
      updateAddress(id, payload),
    onSuccess: () => {
      void invalidateAddresses();
      setEditing(null);
      show('Adresse mise à jour', { variant: 'success' });
    },
    onError: (error) =>
      show(error instanceof Error ? error.message : 'Impossible de modifier l’adresse.', {
        variant: 'error',
      }),
    onSettled: () => setSavingId(null),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteAddress,
    onSuccess: () => {
      void invalidateAddresses();
      show('Adresse supprimée', { variant: 'success' });
    },
    onError: (error) =>
      show(error instanceof Error ? error.message : 'Impossible de supprimer l’adresse.', {
        variant: 'error',
      }),
    onSettled: () => setSavingId(null),
  });
  const defaultMutation = useMutation({
    mutationFn: setDefaultAddress,
    onSuccess: () => {
      void invalidateAddresses();
      show('Adresse par défaut définie', { variant: 'success' });
    },
    onError: (error) =>
      show(
        error instanceof Error ? error.message : 'Impossible de définir l’adresse par défaut.',
        { variant: 'error' },
      ),
  });

  const handleCreate = useCallback(() => {
    if (!form.name || !form.address || !form.postalCode || !form.city) return;
    setSavingId('new');
    createMutation.mutate(addressFormToPayload(form));
  }, [createMutation, form]);

  const openCreate = () => {
    setForm(emptyAddressForm());
    setCreating(true);
  };
  const closeCreate = () => {
    if (savingId === 'new') return;
    setCreating(false);
    setForm(emptyAddressForm());
  };
  const openEdit = (address: AddressDto) => {
    setEditing(address);
    setEditForm(addressToForm(address));
  };
  const closeEdit = () => {
    if (savingId === editing?.id) return;
    setEditing(null);
    setEditForm(emptyAddressForm());
  };
  const handleUpdate = useCallback(() => {
    if (!editing) return;
    setSavingId(editing.id);
    updateMutation.mutate({ id: editing.id, payload: addressFormToPayload(editForm) });
  }, [editForm, editing, updateMutation]);

  const handleDelete = (id: number) => {
    setSavingId(id);
    deleteMutation.mutate(id);
  };

  const handleSetDefault = (id: number) => {
    defaultMutation.mutate(id);
  };

  return {
    editForm,
    editing,
    form,
    creating,
    closeCreate,
    closeEdit,
    handleCreate,
    handleDelete,
    handleSetDefault,
    handleUpdate,
    items: addressesQuery.data ?? [],
    loading: addressesQuery.isLoading,
    openCreate,
    openEdit,
    savingId,
    setEditForm,
    setForm,
  };
};
