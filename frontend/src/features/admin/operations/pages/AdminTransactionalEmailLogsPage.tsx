import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import {
  List,
  OperationsHeader,
} from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatFrenchDateTime } from '@/shared/lib/formatters';

export const AdminTransactionalEmailLogsPage = () => {
  const operations = useAdminOperations({ emails: true, overview: false, support: false, refunds: false, stock: false, fulfillment: false });

  return (
    <PageContainer size="admin" title="Emails transactionnels">
      <OperationsHeader
        description="Historique des emails envoyés, échecs de livraison et scénarios transactionnels associés."
        message={operations.message}
        status={operations.status}
      />
      <section className="mb-8">
        <List
          title="Journal des emails"
          meta={operations.emailsMeta}
          onPageChange={operations.setEmailsPage}
          items={operations.emails.map((item, index) => ({
            key: `${item.createdAt}-${index}`,
            title: `${item.statusLabel ?? (item.status === 'failed' ? 'Échec' : 'Envoyé')} · ${item.scenarioLabel ?? item.scenario}`,
            meta: `${item.recipient || 'Destinataire inconnu'} · ${item.related?.label || item.subject || ''} · ${formatFrenchDateTime(item.createdAt)}`,
          }))}
        />
      </section>
    </PageContainer>
  );
};
