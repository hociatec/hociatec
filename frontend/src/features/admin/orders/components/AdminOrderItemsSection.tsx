import type { OrderDto } from '@/features/orders/publicApi';
import { formatEuroCents } from '@/shared/lib/formatters';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';

type AdminOrderItemsSectionProps = {
  items: OrderDto['items'];
};

export const AdminOrderItemsSection = ({ items }: AdminOrderItemsSectionProps) => {
  const itemsPagination = useAdminPagination(items);

  return (
    <section className="rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
      <div className="mb-4">
        <h2 className="text-lg font-semibold text-brand-900">Articles</h2>
        <p className="mt-1 text-sm text-stone-500">
          Détail des produits, quantités et montants de cette commande.
        </p>
      </div>
      <div className="space-y-3">
        {itemsPagination.paginatedItems.map((item) => (
          <div
            key={item.orderItemId}
            className="flex items-center justify-between gap-3 rounded-2xl border border-brand-100 bg-brand-50 p-4"
          >
            <div>
              <div className="font-medium text-brand-900">{item.productName}</div>
              <div className="text-sm text-stone-500">
                SKU {item.productSku} · Qté {item.quantity}
              </div>
            </div>
            <div className="text-sm font-semibold text-stone-800">
              {formatEuroCents(item.linePriceCents)}
            </div>
          </div>
        ))}
      </div>
      <PaginationControls
        page={itemsPagination.page}
        total={itemsPagination.total}
        totalLabel="article"
        totalPages={itemsPagination.totalPages}
        onPageChange={itemsPagination.setPage}
      />
    </section>
  );
};
