import { PageContainer } from '@/shared/components/layout/PageContainer';
import { useAdminOperations } from '@/features/admin/operations/hooks/useAdminOperations';
import {
  List,
  OperationsHeader,
} from '@/features/admin/operations/components/AdminOperationsWidgets';
import { OperationsStockCard } from '@/features/admin/operations/components/OperationsStockCard';
import { StockThresholdAction } from '@/features/admin/operations/components/OperationsRecentActions';
import { formatFrenchDateTime } from '@/shared/lib/formatters';

export const AdminStockOperationsPage = () => {
  const operations = useAdminOperations({ stock: true, overview: false, support: false, refunds: false, emails: false, fulfillment: false });

  return (
    <PageContainer size="admin" title="Stock">
      <OperationsHeader
        description="Corrections manuelles de stock, suivi des mouvements et mise à jour des seuils de stock faible."
        message={operations.message}
        status={operations.status}
      />
      <section className="mb-8 grid gap-6">
        <OperationsStockCard
          stockForm={operations.stockForm}
          setStockForm={operations.setStockForm}
          submitStock={operations.submitStock}
        />
      </section>
      <section className="mb-8">
        <div className="mb-3">
          <h2 className="text-lg font-semibold text-brand-900">Mouvements récents</h2>
          <p className="text-sm text-stone-500">
            Derniers ajustements de stock et accès rapide aux seuils d’alerte.
          </p>
        </div>
        <List
          title="Mouvements de stock"
          meta={operations.stockMeta}
          onPageChange={operations.setStockPage}
          items={operations.stock.map((item) => ({
            key: item.id,
            title: `${item.product.sku} · ${item.product.name}`,
            meta: `${item.delta > 0 ? '+' : ''}${item.delta} · ${item.stockBefore} → ${item.stockAfter} · ${formatFrenchDateTime(item.createdAt)}`,
            action: (
              <StockThresholdAction
                item={item}
                stockThresholds={operations.stockThresholds}
                setStockThresholds={operations.setStockThresholds}
                submitStockThreshold={operations.submitStockThreshold}
              />
            ),
          }))}
        />
      </section>
    </PageContainer>
  );
};
