import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';

import {
  fetchAdminCustomerById,
  sendCustomerEmail,
  updateAdminCustomerAdminProfile,
  type AdminCustomerAddressDto,
  type AdminCustomerDetailDto,
} from '@/features/admin/customers/api';
import { type OrderDto } from '@/features/orders/api';
import { useToast } from '@/shared/components/ui/toast';
import {
  type CustomerEmailFormState,
  type EmailTemplatePreset,
  type OrderFilter,
} from '@/features/admin/customers/components/customerDetailShared';

export const useAdminCustomerDetail = (customerId: number) => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const toast = useToast();
  const [customer, setCustomer] = useState<AdminCustomerDetailDto | null>(null);
  const [addresses, setAddresses] = useState<AdminCustomerAddressDto[]>([]);
  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [status, setStatus] = useState<'loading' | 'error' | 'success'>('loading');
  const [error, setError] = useState<string | null>(null);
  const [orderFilter, setOrderFilter] = useState<OrderFilter>('all');
  const [adminNotes, setAdminNotes] = useState('');
  const [adminTagsInput, setAdminTagsInput] = useState('');
  const [saveState, setSaveState] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [saveMessage, setSaveMessage] = useState<string | null>(null);
  const [emailOpen, setEmailOpen] = useState(false);
  const [emailForm, setEmailForm] = useState<CustomerEmailFormState>({ subject: '', message: '' });
  const [emailSending, setEmailSending] = useState(false);
  const emailOnlyView = searchParams.get('panel') === 'email';

  useEffect(() => {
    if (!customerId) {
      setStatus('error');
      setError('Client invalide.');
      return;
    }

    setStatus('loading');
    setError(null);
    void fetchAdminCustomerById(customerId)
      .then((data) => {
        setCustomer(data.customer);
        setAddresses(data.addresses);
        setOrders(data.orders);
        setAdminNotes(data.customer.adminNotes ?? '');
        setAdminTagsInput((data.customer.adminTags ?? []).join(', '));
        setEmailForm({ subject: `Votre compte ${data.customer.fullName} sur Hociatec`, message: '' });
        setStatus('success');
      })
      .catch((e: unknown) => {
        setStatus('error');
        setError(e instanceof Error ? e.message : 'Impossible de charger ce client.');
      });
  }, [customerId]);

  useEffect(() => {
    if (emailOnlyView) setEmailOpen(true);
  }, [emailOnlyView]);

  const latestOrder = orders[0] ?? null;
  const filteredOrders = useMemo(() => {
    switch (orderFilter) {
      case 'open':
        return orders.filter((order) => order.status === 'pending' || order.status === 'confirmed');
      case 'delivered':
        return orders.filter((order) => order.status === 'delivered');
      case 'cancelled':
        return orders.filter((order) => order.status === 'cancelled');
      case 'all':
      default:
        return orders;
    }
  }, [orderFilter, orders]);

  const parsedTags = useMemo(
    () => adminTagsInput.split(',').map((tag) => tag.trim()).filter(Boolean),
    [adminTagsInput],
  );

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
    setSearchParams({ panel: 'email' }, { replace: true });
  };

  const handleSaveAdminProfile = () => {
    if (!customer) return;
    setSaveState('saving');
    setSaveMessage(null);
    void updateAdminCustomerAdminProfile(customer.id, { adminNotes, adminTags: parsedTags })
      .then((result) => {
        setCustomer((current) => current ? { ...current, adminNotes: result.adminNotes ?? null, adminTags: result.adminTags } : current);
        setAdminNotes(result.adminNotes ?? '');
        setAdminTagsInput(result.adminTags.join(', '));
        setSaveState('saved');
        setSaveMessage('Suivi interne enregistré.');
      })
      .catch((e: unknown) => {
        setSaveState('error');
        setSaveMessage(e instanceof Error ? e.message : 'Impossible d’enregistrer le suivi interne.');
      });
  };

  const handleSendEmail = () => {
    if (!customer) return;
    setEmailSending(true);
    void sendCustomerEmail(customer.id, emailForm)
      .then((response) => toast.show(response.message ?? 'L’e-mail a bien été envoyé au client.', { variant: 'success' }))
      .catch((e: unknown) => toast.show(e instanceof Error ? e.message : 'Impossible d’envoyer l’email.', { variant: 'error' }))
      .finally(() => setEmailSending(false));
  };

  const applyEmailPreset = (preset: EmailTemplatePreset) => {
    if (!customer) return;
    setEmailForm({ subject: preset.subject(customer), message: preset.message(customer) });
    toast.show(`Modèle "${preset.label}" appliqué.`, { variant: 'info' });
  };

  return {
    customer, addresses, orders, status, error, orderFilter, adminNotes, adminTagsInput,
    saveState, saveMessage, emailOpen, emailForm, emailSending, emailOnlyView, latestOrder,
    filteredOrders, parsedTags, setAdminNotes, setAdminTagsInput, setEmailForm, setOrderFilter,
    closeEmailComposer, toggleEmailComposer, handleSaveAdminProfile, handleSendEmail, applyEmailPreset,
    navigate,
  };
};
