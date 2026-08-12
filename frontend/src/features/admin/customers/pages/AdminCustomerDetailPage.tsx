import { useParams } from 'react-router';

import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useAdminCustomerDetail } from '@/features/admin/customers/hooks/useAdminCustomerDetail';
import { CustomerAddressesSection } from '@/features/admin/customers/components/CustomerAddressesSection';
import { CustomerAdminProfileSection } from '@/features/admin/customers/components/CustomerAdminProfileSection';
import { CustomerEmailComposer } from '@/features/admin/customers/components/CustomerEmailComposer';
import { CustomerOrdersSection } from '@/features/admin/customers/components/CustomerOrdersSection';
import { CustomerQuickActions } from '@/features/admin/customers/components/CustomerQuickActions';
import { CustomerSummaryCards } from '@/features/admin/customers/components/CustomerSummaryCards';
import { parseNullablePositiveInteger } from '@/shared/lib/parsers';

export const AdminCustomerDetailPage = () => {
  const params = useParams();
  const customerId = parseNullablePositiveInteger(params.customerId) ?? 0;
  const detail = useAdminCustomerDetail(customerId);
  const { customer, addresses, orders, status, error, orderFilter, adminNotes, adminTagsInput,
    saveState, saveMessage, emailOpen, emailForm, emailSending, emailOnlyView, latestOrder,
    ordersMeta, orderStats, parsedTags, setAdminNotes, setAdminTagsInput, setEmailForm, setOrderFilter, setOrderPage,
    closeEmailComposer, toggleEmailComposer, handleSaveAdminProfile, handleSendEmail, applyEmailPreset,
    navigate } = detail;

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
            orderFilter={orderFilter}
            orders={orders}
            ordersMeta={ordersMeta}
            orderStats={orderStats}
            onOrderFilterChange={setOrderFilter}
            onOrderPageChange={setOrderPage}
          />
        </div>
      )}
    </PageContainer>
  );
};
