import type { QuoteDto } from '@/features/quotes/types/quoteTypes';
import { formatEuroCents } from '@/shared/lib/formatters';

export const AdminQuoteDetailItemsTable = ({
  items,
}: {
  items: NonNullable<QuoteDto['items']>;
}) => (
  <div className="quote-table-scroll">
    <table className="catalog-admin-table">
      <thead>
        <tr>
          <th>Article</th>
          <th>Description</th>
          <th>Qté</th>
          <th>PU HT</th>
          <th>TVA</th>
          <th>Total TTC</th>
        </tr>
      </thead>
      <tbody>
        {items.map((item) => (
          <tr key={item.id ?? `${item.name}-${item.quantity}`}>
            <td className="quote-strong">{item.name}</td>
            <td>{item.description || '-'}</td>
            <td>
              {item.quantity}
              {item.unit ? ` ${item.unit}` : ''}
            </td>
            <td>{formatEuroCents(item.unitPriceCents ?? 0)}</td>
            <td>{item.vatRate ?? 0}%</td>
            <td>{formatEuroCents(item.lineTotals?.ttc ?? 0)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);
