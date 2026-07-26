import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';

import {
  fetchAdminCustomerById,
  sendCustomerEmail,
  updateAdminCustomerAdminProfile,
  type AdminCustomerAddressDto,
  type AdminCustomerDetailDto,
} from '@/features/admin/customers/api';
import { type OrderDto } from '@/features/orders/api';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useToast } from '@/shared/components/ui/toast';
import { CustomerAddressesSection } from '@/features/admin/customers/components/CustomerAddressesSection';
import { CustomerAdminProfileSection } from '@/features/admin/customers/components/CustomerAdminProfileSection';
import { CustomerEmailComposer } from '@/features/admin/customers/components/CustomerEmailComposer';
import { CustomerOrdersSection } from '@/features/admin/customers/components/CustomerOrdersSection';
import { CustomerQuickActions } from '@/features/admin/customers/components/CustomerQuickActions';
import { CustomerSummaryCards } from '@/features/admin/customers/components/CustomerSummaryCards';
import {
  type CustomerEmailFormState,
  type EmailTemplatePreset,
  type OrderFilter,
} from '@/features/admin/customers/components/customerDetailShared';

export const AdminCustomerDetailPage = () => {
  const params = useParams();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const toast = useToast();
  const customerId = Number(params.customerId);
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
        setEmailForm({
          subject: `Votre compte ${data.customer.fullName} sur Hociatec`,
          message: '',
        });
        setStatus('success');
      })
      .catch((e: unknown) => {
        setStatus('error');
        setError(e instanceof Error ? e.message : 'Impossible de charger ce client.');
      });
  }, [customerId]);

  useEffect(() => {
    if (searchParams.get('panel') === 'email') {
      setEmailOpen(true);
    }
  }, [searchParams]);

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
    () =>
      adminTagsInput
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean),
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
    void updateAdminCustomerAdminProfile(customer.id, {
      adminNotes,
      adminTags: parsedTags,
    })
      .then((result) => {
        setCustomer((current) =>
          current
            ? {
                ...current,
                adminNotes: result.adminNotes ?? null,
                adminTags: result.adminTags,
              }
            : current,
        );
        setAdminNotes(result.adminNotes ?? '');
        setAdminTagsInput(result.adminTags.join(', '));
        setSaveState('saved');
        setSaveMessage('Suivi interne enregistré.');
      })
      .catch((e: unknown) => {
        setSaveState('error');
        setSaveMessage(
          e instanceof Error ? e.message : 'Impossible d’enregistrer le suivi interne.',
        );
      });
  };

  const handleSendEmail = () => {
    if (!customer) return;

    setEmailSending(true);
    void sendCustomerEmail(customer.id, emailForm)
      .then(() => {
        setEmailSending(false);
        toast.show('E-mail envoyé au client.', { variant: 'success' });
      })
      .catch((e: unknown) => {
        setEmailSending(false);
        toast.show(e instanceof Error ? e.message : 'Impossible d’envoyer l’email.', {
          variant: 'error',
        });
      });
  };

  const applyEmailPreset = (preset: EmailTemplatePreset) => {
    if (!customer) return;

    setEmailForm({
      subject: preset.subject(customer),
      message: preset.message(customer),
    });
    toast.show(`Modèle "${preset.label}" appliqué.`, { variant: 'info' });
  };

  const emailComposerSection = customer ? (
    <CustomerEmailComposer
      customer={customer}
      emailForm={emailForm}
      emailOnlyView={emailOnlyView}
      emailSending={emailSending}
      onApplyPreset={applyEmailPreset}
      onClose={closeEmailComposer}
      onEmailFormChange={setEmailForm}
      onSendEmail={handleSendEmail}
    />
  ) : null;

  return (
    <PageContainer
      size="admin"
      title={emailOnlyView ? 'Envoyer un e-mail' : customer ? customer.fullName : 'Fiche client'}
      headerActions={
        <div className="flex items-center gap-4">
          {emailOnlyView && customer ? (
            <button
              type="button"
              className="underline text-sm"
              onClick={() => navigate(`/admin/customers/${customer.id}`)}
            >
              Retour à la fiche client
            </button>
          ) : null}
          <button
            type="button"
            className="underline text-sm"
            onClick={() => navigate('/admin/customers')}
          >
            Retour aux clients
          </button>
        </div>
      }
    >
      {status === 'loading' && <LoadingState>Chargement...</LoadingState>}
      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      {status === 'success' && customer && emailOnlyView ? emailComposerSection : null}

      {status === 'success' && customer && !emailOnlyView && (
        <div className="space-y-6">
          <CustomerQuickActions
            customer={customer}
            emailOpen={emailOpen}
            latestOrder={latestOrder}
            onToggleEmail={toggleEmailComposer}
          />
          {emailOpen ? emailComposerSection : null}
          <CustomerAdminProfileSection
            adminNotes={adminNotes}
            adminTagsInput={adminTagsInput}
            parsedTags={parsedTags}
            saveMessage={saveMessage}
            saveState={saveState}
            onAdminNotesChange={setAdminNotes}
            onAdminTagsInputChange={setAdminTagsInput}
            onSave={handleSaveAdminProfile}
          />
          <CustomerSummaryCards customer={customer} />
          <CustomerAddressesSection addresses={addresses} />
          <CustomerOrdersSection
            filteredOrders={filteredOrders}
            orderFilter={orderFilter}
            orders={orders}
            onOrderFilterChange={setOrderFilter}
          />
        </div>
      )}
    </PageContainer>
  );
};
