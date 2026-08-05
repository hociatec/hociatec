import { useCallback, useEffect, useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { checkoutOrder, type CheckoutRedirectDto, type OrderDto } from '@/features/orders/publicApi';
import { fetchMyAddresses, type AddressDto } from '@/features/addresses/publicApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { cartQueryKeys } from '@/shared/lib/queryKeys';

const emptyAddresses: AddressDto[] = [];

export const useCartCheckout = (authenticated: boolean) => {
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const addressesQuery = useQuery<AddressDto[], Error>({
    queryKey: cartQueryKeys.checkoutAddresses(),
    queryFn: fetchMyAddresses,
    enabled: authenticated,
  });
  const addresses = authenticated ? (addressesQuery.data ?? emptyAddresses) : emptyAddresses;
  const checkoutMutation = useMutation({
    mutationFn: ({ addressId }: { addressId: number }) => checkoutOrder(addressId),
  });

  useEffect(() => {
    if (!authenticated) {
      setSelectedAddressId(null);
      return;
    }
    setSelectedAddressId((addresses.find((item) => item.isDefault) ?? addresses[0])?.id ?? null);
  }, [addresses, authenticated]);

  const checkout = useCallback(
    async (onRedirect: (url: string) => void) => {
      const addressId = selectedAddressId ?? addresses.find((item) => item.isDefault)?.id;
      if (!addressId)
        throw new Error('Choisissez une adresse de livraison avant de passer au paiement.');
      const result = await checkoutMutation.mutateAsync({ addressId });
      if ((result as CheckoutRedirectDto).mode === 'redirect') {
        onRedirect((result as CheckoutRedirectDto).checkoutUrl);
        return null;
      }
      return result as OrderDto;
    },
    [addresses, checkoutMutation, selectedAddressId],
  );
  return {
    addresses,
    addressesLoading: addressesQuery.isLoading,
    addressesError: addressesQuery.error
      ? getHttpErrorMessage(
          addressesQuery.error,
          'Impossible de charger vos adresses de livraison.',
        )
      : null,
    selectedAddressId,
    setSelectedAddressId,
    isCheckout: checkoutMutation.isPending,
    checkout,
  };
};
