import { useCallback, useEffect, useState } from 'react';
import { checkoutOrder, type CheckoutRedirectDto, type OrderDto } from '@/features/orders/api';
import { fetchMyAddresses, type AddressDto } from '@/features/addresses/api/addressesApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

export const useCartCheckout = (authenticated: boolean) => {
  const [addresses, setAddresses] = useState<AddressDto[]>([]);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [addressesError, setAddressesError] = useState<string | null>(null);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [isCheckout, setIsCheckout] = useState(false);
  useEffect(() => {
    if (!authenticated) {
      setAddresses([]);
      setAddressesError(null);
      setSelectedAddressId(null);
      return;
    }
    setAddressesLoading(true);
    void fetchMyAddresses()
      .then((items) => {
        setAddresses(items);
        setSelectedAddressId((items.find((item) => item.isDefault) ?? items[0])?.id ?? null);
      })
      .catch((reason) => {
        setAddresses([]);
        setSelectedAddressId(null);
        setAddressesError(
          getHttpErrorMessage(reason, 'Impossible de charger vos adresses de livraison.'),
        );
      })
      .finally(() => setAddressesLoading(false));
  }, [authenticated]);
  const checkout = useCallback(
    async (onRedirect: (url: string) => void) => {
      const addressId = selectedAddressId ?? addresses.find((item) => item.isDefault)?.id;
      if (!addressId)
        throw new Error('Choisissez une adresse de livraison avant de passer au paiement.');
      setIsCheckout(true);
      try {
        const result = await checkoutOrder(addressId);
        if ((result as CheckoutRedirectDto).mode === 'redirect') {
          onRedirect((result as CheckoutRedirectDto).checkoutUrl);
          return null;
        }
        return result as OrderDto;
      } finally {
        setIsCheckout(false);
      }
    },
    [addresses, selectedAddressId],
  );
  return {
    addresses,
    addressesLoading,
    addressesError,
    selectedAddressId,
    setSelectedAddressId,
    isCheckout,
    checkout,
  };
};
