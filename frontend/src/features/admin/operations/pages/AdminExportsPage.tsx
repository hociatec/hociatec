import { PageContainer } from '@/shared/components/layout/PageContainer';
import {
  OperationsExports,
  OperationsHeader,
} from '@/features/admin/operations/components/AdminOperationsWidgets';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';

const exportLabels: Record<string, string> = {
  orders: 'Commandes',
  customers: 'Clients',
  products: 'Produits',
  quotes: 'Devis',
  refunds: 'Remboursements',
  support: 'SAV',
};

export const AdminExportsPage = () => {
  const operations = useAdminOperations({ overview: false, support: false, refunds: false, stock: false, emails: false, fulfillment: false });

  return (
    <PageContainer size="admin" title="Exports CSV">
      <OperationsHeader
        description="Téléchargement des exports d’exploitation pour contrôle, comptabilité ou reporting."
        message={operations.message}
        status={operations.status}
      />
      <OperationsExports exportLabels={exportLabels} />
    </PageContainer>
  );
};
