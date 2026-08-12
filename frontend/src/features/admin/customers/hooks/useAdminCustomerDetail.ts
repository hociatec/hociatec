import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import {
  fetchAdminCustomerById,
  sendCustomerEmail,
  updateAdminCustomerAdminProfile,
  type AdminCustomerAddressDto,
} from '@/features/admin/customers/api';
import { type OrderDto } from '@/features/orders/publicApi';
import { useToast } from '@/shared/components/ui/toast';
import {
  type CustomerEmailFormState,
  type EmailTemplatePreset,
  type OrderFilter,
} from '@/features/admin/customers/components/customerDetailShared';
import { adminCustomerQueryKeys } from '@/features/admin/customers/queryKeys';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const useAdminCustomerDetail = (customerId: number) => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const toast = useToast();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [adminNotes, setAdminNotes] = useState('');
  const [adminTagsInput, setAdminTagsInput] = useState('');
  const [saveState, setSaveState] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [saveMessage, setSaveMessage] = useState<string | null>(null);
  const [emailOpen, setEmailOpen] = useState(false);
  const [emailForm, setEmailForm] = useState<CustomerEmailFormState>({ subject: '', message: '' });
  const emailOnlyView = searchParams.get('panel') === 'email';
  const orderFilterParam = searchParams.get('orderStatus');
  const orderFilter: OrderFilter =
    orderFilterParam === 'open' || orderFilterParam === 'delivered' || orderFilterParam === 'cancelled'
      ? orderFilterParam
      : 'all';
  const orderPage = parseNullablePositiveInteger(searchParams.get('orderPage')) ?? 1;
  const customerQuery = useQuery({
    queryKey: [...adminCustomerQueryKeys.detail(customerId || null), { orderFilter, orderPage }],
    queryFn: () => fetchAdminCustomerById(customerId, { orderStatus: orderFilter, orderPage, orderPerPage: 10 }),
    enabled: Boolean(customerId),
  });
  const customer = customerQuery.data?.customer ?? null;
  const addresses: AdminCustomerAddressDto[] = customerQuery.data?.addresses ?? [];
  const orders: OrderDto[] = customerQuery.data?.orders.items ?? [];
  const ordersMeta = customerQuery.data?.orders.meta ?? { page: orderPage, perPage: 10, total: 0, totalPages: 1 };
  const orderStats = customerQuery.data?.orders.stats ?? { all: 0, open: 0, delivered: 0, cancelled: 0 };
  const saveProfileMutation = useMutation({
    mutationFn: () =>
      updateAdminCustomerAdminProfile(customerId, { adminNotes, adminTags: parsedTags }),
    onSuccess: (result) => {
      queryClient.setQueryData<Awaited<ReturnType<typeof fetchAdminCustomerById>>>(
        adminCustomerQueryKeys.detail(customerId),
        (current) =>
          current
            ? {
                ...current,
                customer: {
                  ...current.customer,
                  adminNotes: result.adminNotes ?? null,
                  adminTags: result.adminTags,
                },
              }
            : current,
      );
      setAdminNotes(result.adminNotes ?? '');
      setAdminTagsInput(result.adminTags.join(', '));
      setSaveState('saved');
      setSaveMessage('Suivi interne enregistré.');
    },
    onError: (e) => {
      setSaveState('error');
      setSaveMessage(getHttpErrorMessage(e, 'Impossible d’enregistrer le suivi interne.'));
    },
  });
  const sendEmailMutation = useMutation({
    mutationFn: () => sendCustomerEmail(customerId, emailForm),
    onSuccess: (response) =>
      toast.show(response.message ?? 'L’e-mail a bien été envoyé au client.', {
        variant: 'success',
      }),
    onError: (e) =>
      toast.show(getHttpErrorMessage(e, 'Impossible d’envoyer l’email.'), { variant: 'error' }),
  });

  useEffect(() => {
    if (!customerId) {
      setError('Client invalide.');
      return;
    }
    setError(null);
  }, [customerId]);

  useEffect(() => {
    if (!customerQuery.data) return;
    setAdminNotes(customerQuery.data.customer.adminNotes ?? '');
    setAdminTagsInput((customerQuery.data.customer.adminTags ?? []).join(', '));
    setEmailForm({
      subject: `Votre compte ${customerQuery.data.customer.fullName} sur Hociatec`,
      message: '',
    });
  }, [customerQuery.data]);

  useEffect(() => {
    if (emailOnlyView) setEmailOpen(true);
  }, [emailOnlyView]);

  const latestOrder = orders[0] ?? null;
  const parsedTags = adminTagsInput.split(',').map((tag) => tag.trim()).filter(Boolean);

  const closeEmailComposer = () => {
    setEmailOpen(false);
    const nextParams = new URLSearchParams(searchParams);
    nextParams.delete('panel');
    setSearchParams(nextParams, { replace: true });
  };

  const toggleEmailComposer = () => {
    if (emailOpen) {
      closeEmailComposer();
      return;
    }
    setEmailOpen(true);
    const nextParams = new URLSearchParams(searchParams);
    nextParams.set('panel', 'email');
    setSearchParams(nextParams, { replace: true });
  };

  const setOrderFilter = (nextFilter: OrderFilter) => {
    const nextParams = new URLSearchParams(searchParams);
    if (nextFilter === 'all') {
      nextParams.delete('orderStatus');
    } else {
      nextParams.set('orderStatus', nextFilter);
    }
    nextParams.delete('orderPage');
    setSearchParams(nextParams, { replace: true });
  };

  const setOrderPage = (updater: (page: number) => number) => {
    const nextPage = updater(orderPage);
    const nextParams = new URLSearchParams(searchParams);
    if (nextPage <= 1) {
      nextParams.delete('orderPage');
    } else {
      nextParams.set('orderPage', String(nextPage));
    }
    setSearchParams(nextParams, { replace: true });
  };

  const handleSaveAdminProfile = () => {
    if (!customer) return;
    setSaveMessage(null);
    saveProfileMutation.mutate();
  };

  const handleSendEmail = () => {
    if (!customer) return;
    sendEmailMutation.mutate();
  };

  const applyEmailPreset = (preset: EmailTemplatePreset) => {
    if (!customer) return;
    setEmailForm({ subject: preset.subject(customer), message: preset.message(customer) });
    toast.show(`Modèle "${preset.label}" appliqué.`, { variant: 'info' });
  };

  return {
    customer, addresses, orders, status: customerQuery.isLoading ? 'loading' : error || customerQuery.error ? 'error' : 'success', error: error ?? customerQuery.error?.message ?? null, orderFilter, adminNotes, adminTagsInput,
    saveState: saveProfileMutation.isPending ? 'saving' : saveState, saveMessage, emailOpen, emailForm, emailSending: sendEmailMutation.isPending, emailOnlyView, latestOrder,
    ordersMeta, orderStats, parsedTags, setAdminNotes, setAdminTagsInput, setEmailForm, setOrderFilter, setOrderPage,
    closeEmailComposer, toggleEmailComposer, handleSaveAdminProfile, handleSendEmail, applyEmailPreset,
    navigate,
  };
};
